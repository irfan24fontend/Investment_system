<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{InventoryMovement, Product, Purchase, PurchaseItem, Supplier};
use App\Services\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BusinessController extends Controller
{
    public function index()
    {
        $products = Product::orderBy('name')->get();
        return view('admin.business', [
            'products' => $products,
            'suppliers' => Supplier::where('is_active', true)->orderBy('name')->get(),
            'purchases' => Purchase::with('supplier', 'items')->latest()->paginate(15),
            'movements' => InventoryMovement::with('product')->latest()->limit(40)->get(),
            'lowStockCount' => $products->filter(fn ($product) => $product->stock_quantity <= $product->low_stock_threshold)->count(),
            'purchaseDue' => Purchase::selectRaw('COALESCE(SUM(subtotal - paid_amount), 0) as amount')->value('amount'),
            'productOptions' => $products->where('is_active', true)->map(fn ($product) => [
                'id' => $product->id, 'name' => $product->name, 'cost' => $product->unit_cost,
            ])->values(),
        ]);
    }

    public function supplierStore(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:180', 'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:180', 'address' => 'nullable|string|max:2000',
            'notes' => 'nullable|string|max:2000',
        ]);
        $supplier = Supplier::create($data);
        Audit::log('supplier.created', $supplier);
        return back()->with('ok', 'Supplier added.');
    }

    public function purchaseStore(Request $request)
    {
        $data = $request->validate([
            'supplier_id' => 'nullable|exists:suppliers,id',
            'paid_amount' => 'nullable|numeric|min:0|max:100000000',
            'notes' => 'nullable|string|max:2000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1|max:1000000',
            'items.*.unit_cost' => 'required|numeric|min:0|max:10000000',
        ]);
        $paid = bcadd((string) ($data['paid_amount'] ?? '0'), '0', 2);
        $purchase = DB::transaction(function () use ($data, $paid, $request) {
            $ids = collect($data['items'])->pluck('product_id')->unique();
            $products = Product::whereIn('id', $ids)->where('is_active', true)->lockForUpdate()->get()->keyBy('id');
            if ($products->count() !== $ids->count()) {
                throw ValidationException::withMessages(['items' => 'A selected product is inactive or unavailable.']);
            }

            $total = '0.00';
            $lines = [];
            foreach ($data['items'] as $item) {
                $product = $products->get((int) $item['product_id']);
                $quantity = (int) $item['quantity'];
                $cost = bcadd((string) $item['unit_cost'], '0', 2);
                $lineTotal = bcmul((string) $quantity, $cost, 2);
                $total = bcadd($total, $lineTotal, 2);
                $lines[] = compact('product', 'quantity', 'cost', 'lineTotal');
            }
            if (bccomp($paid, $total, 2) > 0) {
                throw ValidationException::withMessages(['paid_amount' => 'Paid amount cannot exceed the purchase total.']);
            }

            $purchase = Purchase::create([
                'code' => 'PO-'.now()->format('ymd').'-'.Str::upper(Str::random(5)),
                'supplier_id' => $data['supplier_id'] ?? null, 'subtotal' => $total,
                'paid_amount' => $paid, 'notes' => $data['notes'] ?? null,
                'created_by' => $request->user()->id,
            ]);
            foreach ($lines as $line) {
                $product = $line['product'];
                $oldStock = (int) $product->stock_quantity;
                $newStock = $oldStock + $line['quantity'];
                $oldValue = bcmul((string) $oldStock, (string) $product->unit_cost, 2);
                $newValue = bcadd($oldValue, $line['lineTotal'], 2);
                $product->unit_cost = $newStock > 0 ? bcdiv($newValue, (string) $newStock, 2) : $line['cost'];
                $product->stock_quantity = $newStock;
                $product->save();
                PurchaseItem::create([
                    'purchase_id' => $purchase->id, 'product_id' => $product->id,
                    'product_name' => $product->name, 'quantity' => $line['quantity'],
                    'unit_cost' => $line['cost'], 'line_total' => $line['lineTotal'],
                ]);
                InventoryMovement::create([
                    'product_id' => $product->id, 'type' => 'purchase',
                    'quantity_change' => $line['quantity'], 'balance_after' => $newStock,
                    'reference_type' => 'purchase', 'reference_id' => $purchase->id,
                    'notes' => 'Received on '.$purchase->code, 'created_by' => $request->user()->id,
                ]);
            }
            return $purchase;
        });

        Audit::log('purchase.created', $purchase, ['total' => $purchase->subtotal]);
        return back()->with('ok', "Purchase {$purchase->code} recorded; stock updated.");
    }

    public function stockAdjust(Request $request, Product $product)
    {
        $data = $request->validate([
            'quantity_change' => 'required|integer|not_in:0|min:-1000000|max:1000000',
            'notes' => 'required|string|max:1000',
        ]);
        DB::transaction(function () use ($data, $product, $request) {
            $locked = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
            $change = (int) $data['quantity_change'];
            $newBalance = (int) $locked->stock_quantity + $change;
            if ($newBalance < 0) {
                throw ValidationException::withMessages(['quantity_change' => 'Stock adjustment cannot make inventory negative.']);
            }
            $locked->stock_quantity = $newBalance;
            $locked->save();
            InventoryMovement::create([
                'product_id' => $locked->id, 'type' => $change > 0 ? 'adjustment_in' : 'adjustment_out',
                'quantity_change' => $change, 'balance_after' => $newBalance,
                'notes' => $data['notes'], 'created_by' => $request->user()->id,
            ]);
        });
        Audit::log('inventory.adjusted', $product, ['quantity_change' => $data['quantity_change']]);
        return back()->with('ok', 'Stock adjustment saved.');
    }
}
