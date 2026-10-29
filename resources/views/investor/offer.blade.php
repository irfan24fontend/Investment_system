@extends('layouts.app')
@section('content')
<a href="{{ route('offers') }}" class="text-sm text-indigo-700">← All offers</a>
<div class="grid lg:grid-cols-3 gap-6 mt-3">
  <div class="lg:col-span-2 space-y-4">
    <div class="bg-white border rounded-xl p-5">
      <div class="flex items-center gap-2 text-xs mb-2"><span class="px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700">{{ ucwords(str_replace('_',' ',$offer->category)) }}</span><span class="text-slate-400">#{{ $offer->code }}</span></div>
      <h1 class="text-2xl font-bold">{{ $offer->title }}</h1>
      <p class="mt-3 text-slate-700 whitespace-pre-line">{{ $offer->description }}</p>
    </div>
    <div class="bg-white border rounded-xl p-5">
      <h2 class="font-semibold mb-3">Project details</h2>
      <dl class="grid grid-cols-2 gap-y-3 text-sm">
        <dt class="text-slate-500">Product</dt><dd>{{ $offer->product_name }} × {{ number_format($offer->quantity) }}</dd>
        <dt class="text-slate-500">Total project cost</dt><dd>৳{{ number_format($offer->total_cost,2) }}</dd>
        <dt class="text-slate-500">Company contribution</dt><dd>৳{{ number_format($offer->business_contribution,2) }}</dd>
        <dt class="text-slate-500">Return model</dt><dd>@if($offer->targetProfit() !== null)<b>Target profit ৳{{ number_format($offer->targetProfit(),2) }}</b> ({{ rtrim(rtrim($offer->returnPct(),'0'),'.') }}% of your investment)<div class="text-xs text-amber-700">গ্যারান্টি নয়। প্রকল্পের প্রকৃত লাভ target এর কম হলে আপনি কম পাবেন; লোকসান হলে মূলধনও কমতে পারে।</div>@else Share of actual net profit: {{ $offer->settlement_rules['investor_profit_share_pct'] ?? 0 }}%@endif</dd>
        <dt class="text-slate-500">Est. settlement</dt><dd>{{ $offer->estimated_settlement_date ? $offer->estimated_settlement_date->format('d M Y') : 'To be announced' }}</dd>
      </dl>
    </div>
    <div class="bg-amber-50 border border-amber-200 rounded-xl p-5 text-sm text-amber-900"><div class="font-semibold mb-1">⚠ Risk disclosure</div><p class="whitespace-pre-line">{{ $offer->risk_disclosure }}</p></div>
  </div>

  <aside class="lg:sticky lg:top-20 self-start bg-white border rounded-xl p-5 space-y-3 shadow-sm">
    <div class="text-xs text-slate-500">Required investment</div>
    <div class="text-3xl font-bold">৳{{ number_format($offer->required_amount,2) }}</div>
    <div class="text-sm text-slate-600">Closes: <b>{{ $offer->deadline_at->timezone('Asia/Dhaka')->format('d M Y, h:i A') }}</b><br><span class="text-amber-700 font-semibold" data-deadline="{{ $offer->deadline_at->toIso8601String() }}"></span></div>
    <div class="text-sm">Your balance: <b>৳{{ number_format($balance,2) }}</b></div>
    @if($mine)
      <a class="block text-center bg-green-600 text-white rounded-lg py-2 text-sm" href="{{ route('investments.show',$mine) }}">You funded this. View investment</a>
    @elseif($open)
      @if(! auth()->user()->investor_verified_at)<div class="text-xs p-2 bg-amber-50 rounded text-amber-900">Identity verification is required before investing.</div>@endif
      @if(bccomp((string)$balance,(string)$offer->required_amount,2) < 0)<div class="text-xs p-2 bg-red-50 rounded text-red-700">Your balance is lower than the required amount. <a class="underline" href="{{ route('wallet') }}">Add funds</a></div>@endif
      <form method="POST" action="{{ route('offers.invest',$offer) }}" onsubmit="return confirm('Invest ৳{{ number_format($offer->required_amount,2) }}? This is final and the offer will be reserved for you.')" class="space-y-2">@csrf
        <input type="hidden" name="token" value="{{ \Illuminate\Support\Str::uuid() }}">
        <label class="flex gap-2 text-xs text-slate-600"><input type="checkbox" name="agree" value="1" required class="mt-0.5"> I have read the risk disclosure. I understand returns are not guaranteed and I may lose money.</label>
        <button class="w-full bg-indigo-600 hover:bg-indigo-700 text-white py-2.5 rounded-lg font-medium">Invest ৳{{ number_format($offer->required_amount,2) }}</button>
      </form>
      <p class="text-[11px] text-slate-400">Only one investor can fund this offer. You will get an agreement slip right after.</p>
    @else <div class="p-3 bg-red-50 text-red-700 rounded text-sm text-center">This offer is no longer available.</div> @endif
  </aside>
</div>
@endsection
