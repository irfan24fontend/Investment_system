@extends('layouts.app')
@section('content')
<div class="flex flex-wrap justify-between items-end gap-3 mb-5">
  <div><h1 class="text-2xl font-bold">Customer orders</h1><p class="text-sm text-slate-500">Record orders received through WhatsApp or other channels.</p></div>
  <div class="bg-indigo-50 border border-indigo-100 rounded-xl px-4 py-3 text-sm"><span class="text-slate-500">This month’s expected profit</span><div class="text-xl font-bold text-indigo-700">৳{{ number_format($monthProfit,2) }}</div></div>
</div>

<div class="grid lg:grid-cols-3 gap-5 mb-7">
  <form method="POST" action="{{ route('admin.customer-orders.products.store') }}" class="bg-white border rounded-xl p-4 space-y-3">@csrf
    <h2 class="font-semibold">Add product</h2>
    <label class="block text-xs text-slate-500">Product name<input name="name" value="{{ old('name') }}" required maxlength="200" class="mt-1 w-full border rounded-lg p-2 text-sm"></label>
    <label class="block text-xs text-slate-500">SKU / code (optional)<input name="sku" value="{{ old('sku') }}" maxlength="50" class="mt-1 w-full border rounded-lg p-2 text-sm"></label>
    <div class="grid grid-cols-2 gap-2">
      <label class="block text-xs text-slate-500">Unit cost<input name="unit_cost" type="number" min="0" step="0.01" required class="mt-1 w-full border rounded-lg p-2 text-sm"></label>
      <label class="block text-xs text-slate-500">Unit sale price<input name="unit_price" type="number" min="0" step="0.01" required class="mt-1 w-full border rounded-lg p-2 text-sm"></label>
      <label class="block text-xs text-slate-500">Opening stock<input name="opening_stock" type="number" min="0" step="1" value="0" class="mt-1 w-full border rounded-lg p-2 text-sm"></label>
      <label class="block text-xs text-slate-500">Low stock alert at<input name="low_stock_threshold" type="number" min="0" step="1" value="0" class="mt-1 w-full border rounded-lg p-2 text-sm"></label>
    </div>
    <button class="bg-slate-800 text-white rounded-lg px-4 py-2 text-sm">Save product</button>
    <p class="text-xs text-slate-500">Products already used in orders remain in order history if their catalog details change.</p>
  </form>

  <form method="POST" action="{{ route('admin.customer-orders.store') }}" class="lg:col-span-2 bg-white border rounded-xl p-4 space-y-4">@csrf
    <h2 class="font-semibold">Enter customer order</h2>
    <div class="grid sm:grid-cols-2 gap-3">
      <label class="text-xs text-slate-500">Customer name<input name="customer_name" value="{{ old('customer_name') }}" required class="mt-1 w-full border rounded-lg p-2 text-sm"></label>
      <label class="text-xs text-slate-500">Phone<input name="customer_phone" value="{{ old('customer_phone') }}" required class="mt-1 w-full border rounded-lg p-2 text-sm"></label>
      <label class="text-xs text-slate-500">WhatsApp number (optional)<input name="whatsapp_number" value="{{ old('whatsapp_number') }}" class="mt-1 w-full border rounded-lg p-2 text-sm"></label>
      <label class="text-xs text-slate-500">Address<textarea name="delivery_address" rows="1" class="mt-1 w-full border rounded-lg p-2 text-sm">{{ old('delivery_address') }}</textarea></label>
    </div>
    <div>
      <div class="flex justify-between items-center mb-2"><h3 class="font-medium text-sm">Products</h3><button type="button" id="add-order-item" class="text-indigo-700 text-sm font-medium">+ Add item</button></div>
      @if($products->isEmpty())<p class="text-sm text-amber-700 bg-amber-50 border rounded-lg p-3">Add a product first, then enter the order.</p>@endif
      <div id="order-items" class="space-y-2">
        <div class="order-item grid grid-cols-12 gap-2 items-end">
          <label class="col-span-6 sm:col-span-5 text-xs text-slate-500">Product<select name="items[0][product_id]" required class="product-select mt-1 w-full border rounded-lg p-2 text-sm"><option value="">Select product</option>@foreach($products as $p)<option value="{{ $p->id }}" data-cost="{{ $p->unit_cost }}" data-price="{{ $p->unit_price }}">{{ $p->name }} · ৳{{ number_format($p->unit_price,2) }} · stock {{ $p->stock_quantity }}</option>@endforeach</select></label>
          <label class="col-span-2 text-xs text-slate-500">Qty<input name="items[0][quantity]" type="number" min="1" value="1" required class="qty mt-1 w-full border rounded-lg p-2 text-sm"></label>
          <label class="col-span-3 sm:col-span-4 text-xs text-slate-500">Unit sale price<input name="items[0][unit_price]" type="number" min="0" step="0.01" required class="price mt-1 w-full border rounded-lg p-2 text-sm"></label>
          <button type="button" class="remove-item col-span-1 text-red-600 p-2" aria-label="Remove item">×</button>
        </div>
      </div>
      <p class="text-xs text-slate-500 mt-2">Product cost is copied from the catalog when saved. Sale price can be adjusted for this order.</p>
    </div>
    <div class="grid sm:grid-cols-4 gap-3">
      <label class="text-xs text-slate-500">Delivery charge to customer<input name="delivery_charge" type="number" min="0" step="0.01" value="{{ old('delivery_charge',0) }}" class="mt-1 w-full border rounded-lg p-2 text-sm"></label>
      <label class="text-xs text-slate-500">Actual delivery cost<input name="delivery_cost" type="number" min="0" step="0.01" value="{{ old('delivery_cost',0) }}" class="mt-1 w-full border rounded-lg p-2 text-sm"></label>
      <label class="text-xs text-slate-500">Other cost<input name="other_cost" type="number" min="0" step="0.01" value="{{ old('other_cost',0) }}" class="mt-1 w-full border rounded-lg p-2 text-sm"></label>
      <label class="text-xs text-slate-500">Amount received<input name="paid_amount" type="number" min="0" step="0.01" value="{{ old('paid_amount',0) }}" class="mt-1 w-full border rounded-lg p-2 text-sm"></label>
    </div>
    <label class="block text-xs text-slate-500">Internal notes<textarea name="notes" rows="2" class="mt-1 w-full border rounded-lg p-2 text-sm">{{ old('notes') }}</textarea></label>
    <button @disabled($products->isEmpty()) class="bg-indigo-600 disabled:bg-slate-300 text-white rounded-lg px-5 py-2 text-sm font-medium">Save order</button>
  </form>
</div>

<div class="bg-white border rounded-xl p-4 mb-7">
  <h2 class="font-semibold mb-3">Product catalog</h2>
  <div class="overflow-x-auto"><table class="w-full text-sm">
    <thead><tr class="text-left text-slate-500 border-b"><th class="py-2">Product</th><th>SKU</th><th>Stock</th><th>Unit cost</th><th>Sale price</th><th>Unit profit</th></tr></thead>
    <tbody>@forelse($products as $p)<tr class="border-b last:border-0"><td class="py-2">{{ $p->name }}</td><td>{{ $p->sku ?: '—' }}</td><td class="{{ $p->stock_quantity <= $p->low_stock_threshold ? 'text-amber-700 font-semibold' : '' }}">{{ $p->stock_quantity }} {{ $p->stock_quantity <= $p->low_stock_threshold ? '· Low' : '' }}</td><td>৳{{ number_format($p->unit_cost,2) }}</td><td>৳{{ number_format($p->unit_price,2) }}</td><td class="text-green-700">৳{{ number_format((float) $p->unit_price - (float) $p->unit_cost,2) }}</td></tr>@empty<tr><td colspan="6" class="py-4 text-slate-500">No products added yet.</td></tr>@endforelse</tbody>
  </table></div>
</div>

<div class="bg-white border rounded-xl p-4">
  <h2 class="font-semibold mb-3">Order register</h2>
  <div class="overflow-x-auto"><table class="w-full text-sm min-w-[850px]">
    <thead><tr class="text-left text-slate-500 border-b"><th class="py-2">Order</th><th>Customer</th><th>Items</th><th>Total</th><th>Cost</th><th>Expected profit</th><th>Due</th><th>Status</th><th></th></tr></thead>
    <tbody>
    @forelse($rows as $o)
      <tr class="border-b last:border-0 align-top">
        <td class="py-3"><b>{{ $o->code }}</b><div class="text-xs text-slate-500">{{ $o->created_at->timezone('Asia/Dhaka')->format('d M Y, h:i A') }}</div></td>
        <td>{{ $o->customer_name }}<div class="text-xs text-slate-500">{{ $o->customer_phone }}</div></td>
        <td>{{ $o->items->sum('quantity') }} pcs · {{ $o->items->count() }} lines</td>
        <td>৳{{ number_format($o->total_amount,2) }}</td><td>৳{{ number_format($o->total_cost,2) }}</td>
        <td class="{{ (float) $o->profit < 0 ? 'text-red-700' : 'text-green-700' }}">৳{{ number_format($o->profit,2) }}</td>
        <td>৳{{ number_format($o->dueAmount(),2) }}</td>
        <td><form method="POST" action="{{ route('admin.customer-orders.status',$o) }}" class="flex gap-1">@csrf
          <select name="status" class="border rounded p-1">@foreach($statuses as $s)<option value="{{ $s }}" @selected($o->status === $s)>{{ ucfirst($s) }}</option>@endforeach</select><button class="border rounded px-2">Save</button>
        </form></td>
        <td><a href="{{ route('admin.customer-orders.memo',$o) }}" class="inline-block bg-indigo-50 text-indigo-700 rounded px-2 py-1 whitespace-nowrap">Memo / JPG</a></td>
      </tr>
    @empty<tr><td colspan="9" class="py-5 text-center text-slate-500">No customer orders yet.</td></tr>@endforelse
    </tbody>
  </table></div>
  <div class="mt-3">{{ $rows->links() }}</div>
</div>

<script>
(() => {
  const wrap = document.getElementById('order-items');
  const add = document.getElementById('add-order-item');
  if (!wrap || !add) return;
  let nextIndex = 1;
  const options = @json($productOptions);
  function makeRow(index) {
    const row = document.createElement('div'); row.className = 'order-item grid grid-cols-12 gap-2 items-end';
    const select = document.createElement('select'); select.name = 'items[' + index + '][product_id]'; select.required = true; select.className = 'product-select mt-1 w-full border rounded-lg p-2 text-sm'; select.innerHTML = '<option value="">Select product</option>';
    options.forEach(p => { const opt = document.createElement('option'); opt.value = p.id; opt.dataset.price = p.price; opt.dataset.cost = p.cost; opt.textContent = p.name + ' · ৳' + Number(p.price).toFixed(2) + ' · stock ' + p.stock; select.appendChild(opt); });
    const productLabel = document.createElement('label'); productLabel.className = 'col-span-6 sm:col-span-5 text-xs text-slate-500'; productLabel.textContent = 'Product'; productLabel.appendChild(select);
    const qtyLabel = document.createElement('label'); qtyLabel.className = 'col-span-2 text-xs text-slate-500'; qtyLabel.textContent = 'Qty';
    const qty = document.createElement('input'); qty.type = 'number'; qty.min = '1'; qty.value = '1'; qty.required = true; qty.name = 'items[' + index + '][quantity]'; qty.className = 'qty mt-1 w-full border rounded-lg p-2 text-sm'; qtyLabel.appendChild(qty);
    const priceLabel = document.createElement('label'); priceLabel.className = 'col-span-3 sm:col-span-4 text-xs text-slate-500'; priceLabel.textContent = 'Unit sale price';
    const price = document.createElement('input'); price.type = 'number'; price.min = '0'; price.step = '0.01'; price.required = true; price.name = 'items[' + index + '][unit_price]'; price.className = 'price mt-1 w-full border rounded-lg p-2 text-sm'; priceLabel.appendChild(price);
    const remove = document.createElement('button'); remove.type = 'button'; remove.className = 'remove-item col-span-1 text-red-600 p-2'; remove.textContent = '×';
    row.append(productLabel, qtyLabel, priceLabel, remove); return row;
  }
  add.addEventListener('click', () => wrap.appendChild(makeRow(nextIndex++)));
  wrap.addEventListener('click', e => { if (e.target.closest('.remove-item') && wrap.querySelectorAll('.order-item').length > 1) e.target.closest('.order-item').remove(); });
  wrap.addEventListener('change', e => { if (e.target.matches('.product-select')) { const p = e.target.selectedOptions[0]; e.target.closest('.order-item').querySelector('.price').value = p.dataset.price || ''; } });
  wrap.querySelectorAll('.product-select').forEach(select => select.addEventListener('change', () => { const p = select.selectedOptions[0]; select.closest('.order-item').querySelector('.price').value = p.dataset.price || ''; }));
})();
</script>
@endsection
