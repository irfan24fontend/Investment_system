<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\{CustomerOrder, CustomerOrderItem, InvestmentOffer, Order, Product};
use App\Models\InventoryMovement;
use App\Services\Audit;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderController extends Controller {
    const FLOW = ['draft', 'confirmed', 'awaiting_financing', 'financing_secured', 'procurement', 'ready', 'delivered', 'financial_verification', 'settlement_pending', 'settled', 'cancelled', 'disputed'];
    // Order Manager may only move through operational states; settlement states are finance-controlled.
    const MANAGER_STATES = ['confirmed', 'awaiting_financing', 'procurement', 'ready', 'delivered', 'financial_verification', 'cancelled'];

    public function index() { return view('admin.orders', ['rows' => Order::with('offer')->latest()->paginate(20), 'offers' => InvestmentOffer::whereDoesntHave('investment')->get(), 'states' => self::MANAGER_STATES]); }

    public function store(Request $r) {
        $d = $r->validate(['code' => 'required|max:30|unique:orders', 'customer_ref' => 'nullable|max:100', 'supplier' => 'nullable|max:150', 'product_name' => 'required|max:200',
            'quantity' => 'required|integer|min:1', 'purchase_cost' => 'required|numeric|min:0', 'sale_price' => 'nullable|numeric|min:0', 'transport_cost' => 'nullable|numeric|min:0',
            'other_cost' => 'nullable|numeric|min:0', 'delivery_destination' => 'nullable|max:200', 'expected_delivery_date' => 'nullable|date', 'offer_id' => 'nullable|exists:investment_offers,id|unique:orders,offer_id']);
        $o = new Order($d); $o->created_by = $r->user()->id; $o->status = 'draft'; $o->offer_id = $d['offer_id'] ?? null; $o->save();
        Audit::log('order.created', $o);
        return back()->with('ok', 'Order created.');
    }

    public function status(Request $r, Order $order) {
        $to = $r->validate(['to' => ['required', Rule::in(self::MANAGER_STATES)]])['to'];
        if (in_array($order->status, ['settled', 'settlement_pending'], true)) return back()->withErrors(['action' => 'Locked by finance.']);
        if (in_array($to, ['procurement', 'ready', 'delivered'], true) && ! ($order->offer && $order->offer->status === 'funded') && $order->offer_id)
            return back()->withErrors(['action' => 'Financing not secured yet.']);
        $order->forceFill(['status' => $to, 'delivered_at' => $to === 'delivered' ? now() : $order->delivered_at])->save();
        Audit::log('order.status', $order, ['to' => $to]);
        return back()->with('ok', 'Updated.');
    }

    public function customerOrders()
    {
        $rows = CustomerOrder::with('items')->latest()->paginate(20);
        $products = Product::where('is_active', true)->orderBy('name')->get();
        $monthProfit = CustomerOrder::where('status', '!=', 'cancelled')
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->sum('profit');

        return view('admin.customer-orders', [
            'rows' => $rows, 'products' => $products, 'monthProfit' => $monthProfit,
            'productOptions' => $products->map(fn ($product) => [
                'id' => $product->id,
                'name' => $product->name,
                'cost' => $product->unit_cost,
                'price' => $product->unit_price,
                'stock' => $product->stock_quantity,
            ])->values(),
            'statuses' => ['received', 'confirmed', 'processing', 'ready', 'delivered', 'cancelled'],
        ]);
    }

    public function productStore(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:200',
            'sku' => 'nullable|string|max:50|unique:products,sku',
            'unit_cost' => 'required|numeric|min:0|max:10000000',
            'unit_price' => 'required|numeric|min:0|max:10000000',
            'opening_stock' => 'nullable|integer|min:0|max:10000000',
            'low_stock_threshold' => 'nullable|integer|min:0|max:10000000',
        ]);
        $data['unit_cost'] = bcadd((string) $data['unit_cost'], '0', 2);
        $data['unit_price'] = bcadd((string) $data['unit_price'], '0', 2);
        $openingStock = (int) ($data['opening_stock'] ?? 0);
        $product = DB::transaction(function () use ($data, $openingStock, $request) {
            $product = Product::create([
                'name' => $data['name'], 'sku' => $data['sku'] ?? null,
                'unit_cost' => $data['unit_cost'], 'unit_price' => $data['unit_price'],
                'stock_quantity' => $openingStock,
                'low_stock_threshold' => (int) ($data['low_stock_threshold'] ?? 0),
            ]);
            if ($openingStock > 0) {
                InventoryMovement::create([
                    'product_id' => $product->id, 'type' => 'opening_stock',
                    'quantity_change' => $openingStock, 'balance_after' => $openingStock,
                    'notes' => 'Opening stock when product was created.', 'created_by' => $request->user()->id,
                ]);
            }
            return $product;
        });
        Audit::log('customer_product.created', $product, ['sku' => $product->sku]);

        return back()->with('ok', 'Product added.');
    }

    public function customerOrderStore(Request $request)
    {
        $data = $request->validate([
            'customer_name' => 'required|string|max:150',
            'customer_phone' => 'required|string|max:30',
            'whatsapp_number' => 'nullable|string|max:30',
            'delivery_address' => 'nullable|string|max:2000',
            'delivery_charge' => 'nullable|numeric|min:0|max:10000000',
            'delivery_cost' => 'nullable|numeric|min:0|max:10000000',
            'other_cost' => 'nullable|numeric|min:0|max:10000000',
            'paid_amount' => 'nullable|numeric|min:0|max:10000000',
            'notes' => 'nullable|string|max:2000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1|max:100000',
            'items.*.unit_price' => 'nullable|numeric|min:0|max:10000000',
        ]);

        $products = Product::whereIn('id', collect($data['items'])->pluck('product_id')->unique())
            ->where('is_active', true)->get()->keyBy('id');
        if ($products->count() !== collect($data['items'])->pluck('product_id')->unique()->count()) {
            return back()->withErrors(['items' => 'One or more selected products are inactive.'])->withInput();
        }

        $deliveryCharge = bcadd((string) ($data['delivery_charge'] ?? '0'), '0', 2);
        $deliveryCost = bcadd((string) ($data['delivery_cost'] ?? '0'), '0', 2);
        $otherCost = bcadd((string) ($data['other_cost'] ?? '0'), '0', 2);
        $paidAmount = bcadd((string) ($data['paid_amount'] ?? '0'), '0', 2);
        $subtotal = '0.00';
        $productCostTotal = '0.00';
        $lines = [];

        foreach ($data['items'] as $item) {
            $product = $products->get((int) $item['product_id']);
            $qty = (int) $item['quantity'];
            $unitPrice = bcadd((string) ($item['unit_price'] ?? $product->unit_price), '0', 2);
            $unitCost = bcadd((string) $product->unit_cost, '0', 2);
            $lineTotal = bcmul((string) $qty, $unitPrice, 2);
            $lineCost = bcmul((string) $qty, $unitCost, 2);
            $subtotal = bcadd($subtotal, $lineTotal, 2);
            $productCostTotal = bcadd($productCostTotal, $lineCost, 2);
            $lines[] = compact('product', 'qty', 'unitPrice', 'unitCost', 'lineTotal', 'lineCost');
        }

        $totalAmount = bcadd($subtotal, $deliveryCharge, 2);
        if (bccomp($paidAmount, $totalAmount, 2) > 0) {
            return back()->withErrors(['paid_amount' => 'Paid amount cannot be greater than the order total.'])->withInput();
        }
        $totalCost = bcadd(bcadd($productCostTotal, $deliveryCost, 2), $otherCost, 2);
        $profit = bcsub($totalAmount, $totalCost, 2);

        $order = DB::transaction(function () use ($data, $request, $deliveryCharge, $deliveryCost, $otherCost, $paidAmount, $subtotal, $totalAmount, $totalCost, $profit, $lines) {
            $lockedProducts = Product::whereIn('id', collect($lines)->pluck('product.id')->unique())
                ->lockForUpdate()->get()->keyBy('id');
            $requestedQuantities = [];
            foreach ($lines as $line) {
                $productId = $line['product']->id;
                $requestedQuantities[$productId] = ($requestedQuantities[$productId] ?? 0) + $line['qty'];
            }
            foreach ($requestedQuantities as $productId => $quantity) {
                $product = $lockedProducts->get($productId);
                if (! $product || ! $product->is_active) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['items' => 'A selected product is no longer available.']);
                }
                if ($product->stock_quantity < $quantity) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['items' => "Not enough stock for {$product->name}; available: {$product->stock_quantity}."]);
                }
            }
            $order = CustomerOrder::create([
                'code' => 'SO-'.now()->format('ymd').'-'.Str::upper(Str::random(5)),
                'customer_name' => $data['customer_name'], 'customer_phone' => $data['customer_phone'],
                'whatsapp_number' => $data['whatsapp_number'] ?? null, 'delivery_address' => $data['delivery_address'] ?? null,
                'status' => 'received', 'subtotal' => $subtotal, 'delivery_charge' => $deliveryCharge,
                'delivery_cost' => $deliveryCost, 'other_cost' => $otherCost, 'total_amount' => $totalAmount,
                'total_cost' => $totalCost, 'profit' => $profit, 'paid_amount' => $paidAmount,
                'notes' => $data['notes'] ?? null, 'created_by' => $request->user()->id,
            ]);
            foreach ($lines as $line) {
                $product = $lockedProducts->get($line['product']->id);
                $product->stock_quantity -= $line['qty'];
                $product->save();
                CustomerOrderItem::create([
                    'customer_order_id' => $order->id, 'product_id' => $product->id,
                    'product_name' => $product->name, 'quantity' => $line['qty'],
                    'unit_cost' => $line['unitCost'], 'unit_price' => $line['unitPrice'],
                    'line_cost' => $line['lineCost'], 'line_total' => $line['lineTotal'],
                ]);
                InventoryMovement::create([
                    'product_id' => $product->id, 'type' => 'sale',
                    'quantity_change' => -$line['qty'], 'balance_after' => $product->stock_quantity,
                    'reference_type' => 'customer_order', 'reference_id' => $order->id,
                    'notes' => 'Stock issued for '.$order->code, 'created_by' => $request->user()->id,
                ]);
            }
            return $order;
        });

        Audit::log('customer_order.created', $order, ['total' => $totalAmount, 'profit' => $profit]);
        return redirect()->route('admin.customer-orders')->with('ok', "Order {$order->code} saved.");
    }

    public function customerOrderStatus(Request $request, CustomerOrder $customerOrder)
    {
        $status = $request->validate(['status' => ['required', Rule::in(['received', 'confirmed', 'processing', 'ready', 'delivered', 'cancelled'])]])['status'];
        DB::transaction(function () use ($customerOrder, $status, $request) {
            $order = CustomerOrder::with('items')->lockForUpdate()->findOrFail($customerOrder->id);
            if ($order->status === $status) return;
            if ($status === 'cancelled' && $order->status !== 'cancelled') {
                foreach ($order->items as $item) {
                    if (! $item->product_id) continue;
                    $product = Product::whereKey($item->product_id)->lockForUpdate()->first();
                    if (! $product) continue;
                    $product->stock_quantity += $item->quantity;
                    $product->save();
                    InventoryMovement::create([
                        'product_id' => $product->id, 'type' => 'sale_cancelled',
                        'quantity_change' => $item->quantity, 'balance_after' => $product->stock_quantity,
                        'reference_type' => 'customer_order', 'reference_id' => $order->id,
                        'notes' => 'Stock returned for '.$order->code, 'created_by' => $request->user()->id,
                    ]);
                }
            } elseif ($order->status === 'cancelled' && $status !== 'cancelled') {
                foreach ($order->items as $item) {
                    if (! $item->product_id) continue;
                    $product = Product::whereKey($item->product_id)->lockForUpdate()->first();
                    if (! $product || $product->stock_quantity < $item->quantity) {
                        throw \Illuminate\Validation\ValidationException::withMessages(['status' => "Cannot reopen order; insufficient stock for {$item->product_name}."]);
                    }
                    $product->stock_quantity -= $item->quantity;
                    $product->save();
                    InventoryMovement::create([
                        'product_id' => $product->id, 'type' => 'sale_reopened',
                        'quantity_change' => -$item->quantity, 'balance_after' => $product->stock_quantity,
                        'reference_type' => 'customer_order', 'reference_id' => $order->id,
                        'notes' => 'Stock reissued for '.$order->code, 'created_by' => $request->user()->id,
                    ]);
                }
            }
            $order->forceFill(['status' => $status])->save();
        });
        Audit::log('customer_order.status', $customerOrder, ['status' => $status]);
        return back()->with('ok', 'Customer order status updated.');
    }

    public function customerOrderMemo(CustomerOrder $customerOrder)
    {
        $customerOrder->load('items');
        return view('admin.customer-order-memo', [
            'order' => $customerOrder,
            'memo' => [
                'company' => 'Inayasupplies',
                'company_phone' => '01603600812',
                'code' => $customerOrder->code,
                'date' => $customerOrder->created_at->timezone('Asia/Dhaka')->format('d M Y, h:i A'),
                'customer' => $customerOrder->customer_name,
                'phone' => $customerOrder->customer_phone,
                'whatsapp' => $customerOrder->whatsapp_number,
                'address' => $customerOrder->delivery_address,
                'items' => $customerOrder->items->map(fn ($item) => [
                    'name' => $item->product_name, 'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price, 'total' => $item->line_total,
                ])->values(),
                'subtotal' => $customerOrder->subtotal,
                'delivery_charge' => $customerOrder->delivery_charge,
                'total' => $customerOrder->total_amount,
                'paid' => $customerOrder->paid_amount,
                'due' => $customerOrder->dueAmount(),
            ],
        ]);
    }
}
