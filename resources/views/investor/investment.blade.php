@extends('layouts.app')
@section('content')
@php
  $snap = $i->agreement_snapshot; $s = $i->settlement && $i->settlement->status==='posted' ? $i->settlement : null;
  $map = ['procurement'=>2,'ready'=>2,'delivered'=>3,'financial_verification'=>4,'settlement_pending'=>4,'settled'=>5];
  $key = $order->status ?? ($updates->first()->stage ?? ''); $stage = in_array($i->status,['settled','loss_settled']) ? 5 : ($map[$key] ?? 1);
  $steps = ['Funded','Procurement','Delivered','Verification','Settled'];
@endphp
<a href="{{ route('investments') }}" class="text-sm text-indigo-700">← My investments</a>
<div class="flex flex-wrap items-start justify-between gap-3 mt-3 mb-5">
  <div><div class="text-xs text-slate-500">INV-{{ str_pad($i->id,6,'0',STR_PAD_LEFT) }}</div><h1 class="text-2xl font-bold">{{ $i->offer->title }}</h1></div>
  <div class="flex gap-2"><a href="{{ route('investments.receipt',$i) }}" target="_blank" class="px-3 py-2 border rounded-lg text-sm">View / Print</a>
    <a href="{{ route('investments.receipt',[$i,'download'=>1]) }}" class="px-3 py-2 bg-indigo-600 text-white rounded-lg text-sm">⬇ Download agreement slip</a></div>
</div>

<div class="grid md:grid-cols-3 gap-3 mb-5">
  <div class="bg-white border rounded-xl p-4"><div class="text-xs text-slate-500">Invested</div><div class="text-xl font-bold">৳{{ number_format($i->amount,2) }}</div></div>
  <div class="bg-white border rounded-xl p-4"><div class="text-xs text-slate-500">Status</div><div class="mt-1">@include('partials.badge',['s'=>$i->status,'kind'=>'investment'])</div></div>
  <div class="bg-white border rounded-xl p-4"><div class="text-xs text-slate-500">{{ $s ? 'Final settlement' : 'Est. settlement' }}</div>
    <div class="text-xl font-bold">{{ $s ? '৳'.number_format($s->payout,2) : ($i->offer->estimated_settlement_date ? $i->offer->estimated_settlement_date->format('d M Y') : 'TBD') }}</div></div>
</div>

<div class="bg-white border rounded-xl p-5 mb-5">
  <h2 class="font-semibold mb-4">Progress</h2>
  <ol class="flex items-center">
    @foreach($steps as $k=>$name)
      <li class="flex-1 flex flex-col items-center text-center relative">
        <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold {{ ($k+1)<=$stage ? 'bg-green-600 text-white' : 'bg-slate-200 text-slate-500' }}">{{ ($k+1)<=$stage ? '✓' : $k+1 }}</div>
        <div class="text-[11px] mt-1 {{ ($k+1)<=$stage ? 'text-slate-800 font-medium' : 'text-slate-400' }}">{{ $name }}</div>
      </li>
    @endforeach
  </ol>
  @if($order && $order->status==='cancelled')<p class="mt-3 text-sm text-red-700">The underlying order was cancelled. Settlement will be calculated from actual costs and recoveries.</p>@endif
  @if($order && $order->status==='disputed')<p class="mt-3 text-sm text-red-700">The order is under review. Please contact support.</p>@endif
</div>

@if($s)
<div class="mb-5 p-4 rounded-xl border {{ $s->investor_loss>0 ? 'bg-orange-50 border-orange-200 text-orange-900' : 'bg-green-50 border-green-200 text-green-900' }}">
  <b>Investment completed.</b> ৳{{ number_format($s->payout,2) }} has been credited to your wallet (your capital ৳{{ number_format($i->amount,2) }}
  {{ $s->investor_loss>0 ? '− loss ৳'.number_format($s->investor_loss,2) : '+ profit ৳'.number_format($s->investor_profit,2) }}).
  <a href="{{ route('wallet') }}" class="underline">Open wallet</a>
</div>
@endif
<div class="bg-white border rounded-xl p-5 mb-5"><h2 class="font-semibold mb-3">Updates from the company</h2>
  @if($updates->isNotEmpty())<div class="mb-4"><div class="flex justify-between text-xs text-slate-500 mb-1"><span>Latest progress</span><span>{{ $updates->first()->percent }}%</span></div><div class="h-2 bg-slate-200 rounded"><div class="h-2 bg-indigo-600 rounded" style="width: {{ $updates->first()->percent }}%"></div></div></div>@endif
  <div class="space-y-3 text-sm">@forelse($updates as $u)<div class="border-l-2 border-indigo-200 pl-3"><div class="text-xs text-slate-400">{{ \Illuminate\Support\Carbon::parse($u->created_at)->timezone('Asia/Dhaka')->format('d M Y, h:i A') }} · {{ ucwords(str_replace('_',' ',$u->stage)) }}</div>{{ $u->note }}</div>@empty<div class="text-slate-500">No updates yet. The company will post progress here.</div>@endforelse</div>
</div>
@if($s)
<div class="bg-white border rounded-xl p-5 mb-5"><h2 class="font-semibold mb-3">Settlement statement</h2>
  <dl class="grid grid-cols-2 gap-y-2 text-sm">
    <dt class="text-slate-500">Revenue received</dt><dd>৳{{ number_format($s->revenue_received,2) }}</dd>
    <dt class="text-slate-500">Refunds / returns</dt><dd>৳{{ number_format($s->refunds,2) }}</dd>
    <dt class="text-slate-500">Project costs</dt><dd>৳{{ number_format($s->total_costs,2) }}</dd>
    <dt class="text-slate-500">Project net result</dt><dd class="{{ $s->net_result<0?'text-red-700':'' }}">৳{{ number_format($s->net_result,2) }}</dd>
    <dt class="text-slate-500">Your profit</dt><dd>৳{{ number_format($s->investor_profit,2) }}</dd>
    <dt class="text-slate-500">Your loss</dt><dd>৳{{ number_format($s->investor_loss,2) }}</dd>
    <dt class="font-semibold">Credited to wallet</dt><dd class="font-bold">৳{{ number_format($s->payout,2) }}</dd>
  </dl></div>
@else
<p class="text-sm text-slate-500 mb-5">Not yet settled. Gains or losses are realized only after delivery, payment and costs are verified and approved.</p>
@endif

<div class="bg-white border rounded-xl p-5"><h2 class="font-semibold mb-3">Agreement you accepted</h2>
  <dl class="grid grid-cols-2 gap-y-2 text-sm">
    <dt class="text-slate-500">Accepted on</dt><dd>{{ \Illuminate\Support\Carbon::parse($snap['accepted_at'])->timezone('Asia/Dhaka')->format('d M Y, h:i A') }}</dd>
    <dt class="text-slate-500">Return model</dt><dd>@if(isset($snap['rules']['investor_return_pct']))Target {{ $snap['rules']['investor_return_pct'] }}% of invested amount (৳{{ number_format((float)($snap['target_profit'] ?? 0),2) }}), not guaranteed @else Share of actual net profit: {{ $snap['rules']['investor_profit_share_pct'] ?? 0 }}% @endif</dd>
  </dl>
  <div class="mt-3 p-3 bg-amber-50 rounded text-sm text-amber-900 whitespace-pre-line"><b>Risk disclosure:</b> {{ $snap['risk'] }}</div>
</div>
@endsection
