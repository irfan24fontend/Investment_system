@extends('layouts.app')
@section('content')
<h1 class="text-xl font-bold mb-3">Ready for settlement</h1>
@forelse($ready as $i)<form method="POST" action="{{ route('admin.settlements.prepare',$i) }}" class="bg-white p-3 rounded shadow mb-2 flex flex-wrap gap-2 items-end">@csrf
<div class="text-sm">#{{ $i->id }} {{ $i->offer->title }} (৳{{ number_format($i->amount,2) }})</div>
<label class="text-xs">Verified revenue received<input name="revenue" type="number" step="0.01" min="0" required class="border rounded p-1 block"></label>
<label class="text-xs">Refunds/returns<input name="refunds" type="number" step="0.01" min="0" class="border rounded p-1 block"></label>
<label class="text-xs">Extra deductions<input name="extra" type="number" step="0.01" min="0" class="border rounded p-1 block"></label>
<button class="bg-indigo-600 text-white px-3 py-1 rounded">Calculate</button></form>@empty<p class="text-slate-500 text-sm">None.</p>@endforelse
<h2 class="font-semibold mt-6 mb-2">Settlements</h2>
<div class="bg-white rounded shadow overflow-x-auto"><table class="w-full text-sm"><tr class="bg-slate-100 text-left"><th class="p-2">#</th><th>Offer</th><th>Net result</th><th>Profit</th><th>Loss</th><th>Payout</th><th>Status</th><th></th></tr>
@foreach($rows as $s)<tr class="border-t"><td class="p-2">{{ $s->id }}</td><td>{{ $s->investment->offer->code }}</td><td class="{{ $s->net_result<0?'text-red-700':'' }}">৳{{ number_format($s->net_result,2) }}</td><td>৳{{ number_format($s->investor_profit,2) }}</td><td>৳{{ number_format($s->investor_loss,2) }}</td><td>৳{{ number_format($s->payout,2) }}</td><td>{{ $s->status }}</td>
<td>@if($s->status==='prepared' && $s->prepared_by!==auth()->id())<form method="POST" action="{{ route('admin.settlements.approve',$s) }}" data-confirm="Approve and credit investor?">@csrf<button class="bg-green-600 text-white px-2 rounded">Approve and post</button></form>@elseif($s->status==='prepared')<span class="text-xs text-slate-500">Needs a different approver</span>@endif</td></tr>@endforeach</table></div>{{ $rows->links() }}
@endsection
