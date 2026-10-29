@extends('layouts.app')
@section('content')
@php $done = $inv && in_array($inv->status,['settled','loss_settled']); $st = $inv?->settlement; @endphp
<a href="{{ route('admin.offers') }}" class="text-sm text-indigo-700">← Back to offers</a>
<div class="flex flex-wrap items-start justify-between gap-2 my-3">
  <div><div class="text-xs text-slate-500">{{ $offer->code }}</div><h1 class="text-xl font-bold">{{ $offer->title }}</h1></div>
  <div class="text-sm text-right">Status: <b>{{ $offer->status }}</b><br>Required: ৳{{ number_format($offer->required_amount,2) }}</div>
</div>

@if(! $inv)<div class="p-4 bg-amber-50 border border-amber-200 rounded-xl text-sm">No investor has funded this offer yet.</div>
@else
<div class="bg-white border rounded-xl p-4 mb-4 text-sm grid sm:grid-cols-3 gap-3">
  <div><div class="text-xs text-slate-500">Investor</div><b>{{ $inv->user->name }}</b><div class="text-xs text-slate-500">{{ $inv->user->phone }}</div></div>
  <div><div class="text-xs text-slate-500">Invested</div><b>৳{{ number_format($inv->amount,2) }}</b></div>
  <div><div class="text-xs text-slate-500">Investment status</div><b>{{ $inv->status }}</b></div>
</div>

@if($done && $st)
<div class="p-4 bg-green-50 border border-green-200 rounded-xl text-sm mb-4"><b>Completed.</b> Revenue ৳{{ number_format($st->revenue_received,2) }}, refunds ৳{{ number_format($st->refunds,2) }}, costs ৳{{ number_format($st->total_costs,2) }}, net ৳{{ number_format($st->net_result,2) }}.
 Investor profit ৳{{ number_format($st->investor_profit,2) }}, loss ৳{{ number_format($st->investor_loss,2) }}. <b>Credited to wallet: ৳{{ number_format($st->payout,2) }}</b></div>
@else
<div class="grid lg:grid-cols-2 gap-4 mb-6">
  <form method="POST" action="{{ route('admin.offers.progress.store',$offer) }}" class="bg-white border rounded-xl p-4 space-y-2">@csrf
    <h2 class="font-semibold">Post a progress update</h2>
    <select name="stage" class="border rounded-lg px-3 py-2 w-full text-sm">
      @foreach(['note'=>'General update','procurement'=>'Procurement in progress','ready'=>'Ready for delivery','delivered'=>'Delivered','financial_verification'=>'Financial verification'] as $k=>$l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select>
    <label class="text-xs text-slate-500">Progress %<input name="percent" type="number" min="0" max="100" value="{{ $updates->first()->percent ?? 0 }}" required class="border rounded-lg px-3 py-2 w-full text-sm"></label>
    <textarea name="note" rows="3" required placeholder="What happened? The investor will see this." class="border rounded-lg px-3 py-2 w-full text-sm"></textarea>
    <button class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm">Post update</button>
  </form>

  <form method="POST" action="{{ route('admin.offers.complete',$offer) }}" data-confirm="Complete this offer now? The investor wallet will be credited immediately. This cannot be undone." class="bg-white border-2 border-green-300 rounded-xl p-4 space-y-2">@csrf
    <h2 class="font-semibold text-green-800">Complete offer and pay investor</h2>
    <p class="text-xs text-slate-500">Enter the real figures. The system calculates profit or loss under the agreement and credits principal ± result to the investor's wallet.</p>
    <label class="text-xs text-slate-500">Revenue actually received (৳)<input name="revenue" type="number" step="0.01" min="0" required class="border rounded-lg px-3 py-2 w-full text-sm"></label>
    <label class="text-xs text-slate-500">Refunds / returns (৳)<input name="refunds" type="number" step="0.01" min="0" value="0" class="border rounded-lg px-3 py-2 w-full text-sm"></label>
    <label class="text-xs text-slate-500">Actual total project cost (৳)<input name="actual_cost" type="number" step="0.01" min="0" value="{{ $defaultCost }}" required class="border rounded-lg px-3 py-2 w-full text-sm"></label>
    <div class="text-xs text-slate-500">Investor profit share: {{ $offer->settlement_rules['investor_profit_share_pct'] ?? 0 }}% of net profit. Loss is shared by funding ratio.</div>
    <label class="flex gap-2 text-xs"><input type="checkbox" name="confirm" value="1" required> I have verified these figures.</label>
    <button class="bg-green-600 text-white px-4 py-2 rounded-lg text-sm w-full">Complete and credit investor</button>
  </form>
</div>
@endif
@endif

<h2 class="font-semibold mb-2">Update history</h2>
<div class="bg-white border rounded-xl divide-y text-sm">
@forelse($updates as $u)<div class="p-3"><div class="flex justify-between"><b>{{ ucwords(str_replace('_',' ',$u->stage)) }} · {{ $u->percent }}%</b><span class="text-xs text-slate-400">{{ \Illuminate\Support\Carbon::parse($u->created_at)->timezone('Asia/Dhaka')->format('d M Y, h:i A') }}</span></div><div class="text-slate-600">{{ $u->note }}</div></div>
@empty<div class="p-4 text-slate-500">No updates yet.</div>@endforelse</div>
@endsection
