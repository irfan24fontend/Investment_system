@extends('layouts.app')
@section('content')
<div class="flex flex-wrap justify-between items-end gap-3 mb-5">
  <div><h1 class="text-2xl font-bold">Inventory & Purchases</h1><p class="text-sm text-slate-500">Track stock, suppliers, incoming purchases and supplier balances.</p></div>
  <div class="flex gap-3"><div class="bg-amber-50 border border-amber-100 rounded-xl px-4 py-3 text-sm"><span class="text-slate-500">Low stock products</span><div class="text-xl font-bold text-amber-700">{{ $lowStockCount }}</div></div><div class="bg-indigo-50 border border-indigo-100 rounded-xl px-4 py-3 text-sm"><span class="text-slate-500">Purchase due</span><div class="text-xl font-bold text-indigo-700">৳{{ number_format((float) $purchaseDue,2) }}</div></div></div>
</div>

<div class="grid lg:grid-cols-3 gap-5 mb-6">
  <form method="POST" action="{{ route('admin.business.suppliers.store') }}" class="bg-white border rounded-xl p-4 space-y-3">@csrf
    <h2 class="font-semibold">Add supplier</h2>
    <label class="block text-xs text-slate-500">Supplier name<input name="name" required maxlength="180" class="mt-1 w-full border rounded-lg p-2 text-sm"></label>
    <div class="grid grid-cols-2 gap-2"><label class="block text-xs text-slate-500">Phone<input name="phone" class="mt-1 w-full border rounded-lg p-2 text-sm"></label><label class="block text-xs text-slate-500">Email<input name="email" type="email" class="mt-1 w-full border rounded-lg p-2 text-sm"></label></div>
    <label class="block text-xs text-slate-500">Address<textarea name="address" rows="2" class="mt-1 w-full border rounded-lg p-2 text-sm"></textarea></label>
    <button class="bg-slate-800 text-white rounded-lg px-4 py-2 text-sm">Save supplier</button>
  </form>
  <div class="bg-white border rounded-xl p-4 space-y-2"><h2 class="font-semibold">Suppliers</h2>
    @forelse($suppliers as $supplier)<div class="border-b last:border-0 py-2"><div class="text-sm font-medium">{{ $supplier->name }}</div><div class="text-xs text-slate-500">{{ $supplier->phone ?: 'No phone' }}{{ $supplier->email ? ' · '.$supplier->email : '' }}</div></div>@empty<p class="text-sm text-slate-500">No suppliers added.</p>@endforelse
  </div>

  <form method="POST" action="{{ route('admin.business.purchases.store') }}" class="lg:col-span-2 bg-white border rounded-xl p-4 space-y-4">@csrf
    <h2 class="font-semibold">Record received purchase</h2>
    <div class="grid sm:grid-cols-2 gap-3">
      <label class="text-xs text-slate-500">Supplier<select name="supplier_id" class="mt-1 w-full border rounded-lg p-2 text-sm"><option value="">Select supplier (optional)</option>@foreach($suppliers as $supplier)<option value="{{ $supplier->id }}">{{ $supplier->name }}{{ $supplier->phone ? ' · '.$supplier->phone : '' }}</option>@endforeach</select></label>
      <label class="text-xs text-slate-500">Paid to supplier now<input name="paid_amount" type="number" min="0" step="0.01" value="0" class="mt-1 w-full border rounded-lg p-2 text-sm"></label>
    </div>
    <div><div class="flex justify-between items-center mb-2"><h3 class="font-medium text-sm">Purchased items</h3><button type="button" id="add-purchase-item" class="text-indigo-700 text-sm font-medium">+ Add item</button></div>
      @if($products->where('is_active',true)->isEmpty())<p class="text-sm text-amber-700 bg-amber-50 border rounded-lg p-3">Add a product from Customer Orders first.</p>@endif
      <div id="purchase-items" class="space-y-2">
        <div class="purchase-item grid grid-cols-12 gap-2 items-end">
          <label class="col-span-6 sm:col-span-6 text-xs text-slate-500">Product<select name="items[0][product_id]" required class="purchase-product mt-1 w-full border rounded-lg p-2 text-sm"><option value="">Select product</option>@foreach($products->where('is_active',true) as $product)<option value="{{ $product->id }}" data-cost="{{ $product->unit_cost }}">{{ $product->name }} · current cost ৳{{ number_format($product->unit_cost,2) }}</option>@endforeach</select></label>
          <label class="col-span-2 text-xs text-slate-500">Qty<input name="items[0][quantity]" type="number" min="1" value="1" required class="mt-1 w-full border rounded-lg p-2 text-sm"></label>
          <label class="col-span-3 text-xs text-slate-500">Unit cost<input name="items[0][unit_cost]" type="number" min="0" step="0.01" required class="purchase-cost mt-1 w-full border rounded-lg p-2 text-sm"></label>
          <button type="button" class="remove-purchase-item col-span-1 text-red-600 p-2" aria-label="Remove item">×</button>
        </div>
      </div>
    </div>
    <label class="block text-xs text-slate-500">Purchase notes<textarea name="notes" rows="2" class="mt-1 w-full border rounded-lg p-2 text-sm"></textarea></label>
    <p class="text-xs text-slate-500">Saving a purchase increases stock immediately. Product cost is updated using weighted average cost.</p>
    <button @disabled($products->where('is_active',true)->isEmpty()) class="bg-indigo-600 disabled:bg-slate-300 text-white rounded-lg px-5 py-2 text-sm font-medium">Save purchase & receive stock</button>
  </form>
</div>

<div class="bg-white border rounded-xl p-4 mb-6">
  <h2 class="font-semibold mb-3">Current stock</h2>
  <div class="overflow-x-auto"><table class="w-full text-sm min-w-[850px]"><thead><tr class="text-left text-slate-500 border-b"><th class="py-2">Product</th><th>SKU</th><th>On hand</th><th>Low stock at</th><th>Average cost</th><th>Sale price</th><th>Adjust stock</th></tr></thead><tbody>
  @forelse($products as $product)<tr class="border-b last:border-0"><td class="py-2">{{ $product->name }}{{ !$product->is_active ? ' (inactive)' : '' }}</td><td>{{ $product->sku ?: '—' }}</td><td class="{{ $product->stock_quantity <= $product->low_stock_threshold ? 'text-amber-700 font-semibold' : '' }}">{{ $product->stock_quantity }}</td><td>{{ $product->low_stock_threshold }}</td><td>৳{{ number_format($product->unit_cost,2) }}</td><td>৳{{ number_format($product->unit_price,2) }}</td><td><form method="POST" action="{{ route('admin.business.stock-adjust',$product) }}" class="flex items-center gap-1">@csrf<input name="quantity_change" type="number" step="1" required placeholder="±qty" class="w-20 border rounded p-1"><input name="notes" required placeholder="Reason" class="w-28 border rounded p-1"><button class="bg-slate-100 border rounded px-2 py-1">Apply</button></form></td></tr>@empty<tr><td colspan="7" class="py-4 text-slate-500">No products yet.</td></tr>@endforelse
  </tbody></table></div>
</div>

<div class="grid lg:grid-cols-2 gap-5">
  <div class="bg-white border rounded-xl p-4"><h2 class="font-semibold mb-3">Purchase register</h2><div class="overflow-x-auto"><table class="w-full text-sm min-w-[560px]"><thead><tr class="text-left text-slate-500 border-b"><th class="py-2">Purchase</th><th>Supplier</th><th>Total</th><th>Paid</th><th>Due</th><th>Items</th></tr></thead><tbody>
  @forelse($purchases as $purchase)<tr class="border-b last:border-0"><td class="py-2">{{ $purchase->code }}<div class="text-xs text-slate-500">{{ $purchase->created_at->timezone('Asia/Dhaka')->format('d M Y, h:i A') }}</div></td><td>{{ $purchase->supplier?->name ?? '—' }}</td><td>৳{{ number_format($purchase->subtotal,2) }}</td><td>৳{{ number_format($purchase->paid_amount,2) }}</td><td class="{{ (float) $purchase->dueAmount() > 0 ? 'text-red-700' : '' }}">৳{{ number_format($purchase->dueAmount(),2) }}</td><td>{{ $purchase->items->sum('quantity') }}</td></tr>@empty<tr><td colspan="6" class="py-4 text-slate-500">No purchases yet.</td></tr>@endforelse
  </tbody></table></div><div class="mt-3">{{ $purchases->links() }}</div></div>

  <div class="bg-white border rounded-xl p-4"><h2 class="font-semibold mb-3">Recent stock movements</h2><div class="overflow-x-auto"><table class="w-full text-sm min-w-[480px]"><thead><tr class="text-left text-slate-500 border-b"><th class="py-2">Time</th><th>Product</th><th>Type</th><th>Change</th><th>Balance</th></tr></thead><tbody>
  @forelse($movements as $movement)<tr class="border-b last:border-0"><td class="py-2 text-xs">{{ $movement->created_at->timezone('Asia/Dhaka')->format('d M, h:i A') }}</td><td>{{ $movement->product?->name ?? 'Deleted product' }}</td><td>{{ str_replace('_',' ',ucfirst($movement->type)) }}</td><td class="{{ $movement->quantity_change > 0 ? 'text-green-700' : 'text-red-700' }}">{{ $movement->quantity_change > 0 ? '+' : '' }}{{ $movement->quantity_change }}</td><td>{{ $movement->balance_after }}</td></tr>@empty<tr><td colspan="5" class="py-4 text-slate-500">No stock movement recorded.</td></tr>@endforelse
  </tbody></table></div></div>
</div>

<script>
(() => {
  const wrap = document.getElementById('purchase-items');
  const add = document.getElementById('add-purchase-item');
  if (!wrap || !add) return;
  let nextIndex = 1;
  const options = @json($productOptions);
  function makeRow(index) {
    const row = document.createElement('div'); row.className = 'purchase-item grid grid-cols-12 gap-2 items-end';
    const label = document.createElement('label'); label.className = 'col-span-6 sm:col-span-6 text-xs text-slate-500'; label.textContent = 'Product';
    const select = document.createElement('select'); select.name = 'items[' + index + '][product_id]'; select.required = true; select.className = 'purchase-product mt-1 w-full border rounded-lg p-2 text-sm';
    const empty = document.createElement('option'); empty.value = ''; empty.textContent = 'Select product'; select.appendChild(empty);
    options.forEach(product => { const option = document.createElement('option'); option.value = product.id; option.dataset.cost = product.cost; option.textContent = product.name + ' · current cost ৳' + Number(product.cost).toFixed(2); select.appendChild(option); });
    label.appendChild(select);
    const qtyLabel = document.createElement('label'); qtyLabel.className = 'col-span-2 text-xs text-slate-500'; qtyLabel.textContent = 'Qty';
    const qty = document.createElement('input'); qty.type = 'number'; qty.min = '1'; qty.value = '1'; qty.required = true; qty.name = 'items[' + index + '][quantity]'; qty.className = 'mt-1 w-full border rounded-lg p-2 text-sm'; qtyLabel.appendChild(qty);
    const costLabel = document.createElement('label'); costLabel.className = 'col-span-3 text-xs text-slate-500'; costLabel.textContent = 'Unit cost';
    const cost = document.createElement('input'); cost.type = 'number'; cost.min = '0'; cost.step = '0.01'; cost.required = true; cost.name = 'items[' + index + '][unit_cost]'; cost.className = 'purchase-cost mt-1 w-full border rounded-lg p-2 text-sm'; costLabel.appendChild(cost);
    const remove = document.createElement('button'); remove.type = 'button'; remove.className = 'remove-purchase-item col-span-1 text-red-600 p-2'; remove.textContent = '×';
    select.addEventListener('change', () => { cost.value = select.selectedOptions[0].dataset.cost || ''; });
    row.append(label, qtyLabel, costLabel, remove); return row;
  }
  add.addEventListener('click', () => wrap.appendChild(makeRow(nextIndex++)));
  wrap.addEventListener('click', event => { if (event.target.closest('.remove-purchase-item') && wrap.querySelectorAll('.purchase-item').length > 1) event.target.closest('.purchase-item').remove(); });
  wrap.querySelector('.purchase-product')?.addEventListener('change', event => { const cost = event.target.closest('.purchase-item').querySelector('.purchase-cost'); cost.value = event.target.selectedOptions[0].dataset.cost || ''; });
})();
</script>
@endsection
