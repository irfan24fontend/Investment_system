@extends('layouts.app')
@section('content')
@php
  $themes = ['order_financing'=>['from-indigo-500','to-violet-600','📦'],'stock_financing'=>['from-emerald-500','to-teal-600','🏭'],'bulk_purchase_financing'=>['from-amber-500','to-orange-600','🧴']];
  $th = $themes[$offer->category] ?? ['from-slate-500','to-slate-700','💼'];
  $share = $offer->settlement_rules['investor_profit_share_pct'] ?? 0;
  $amt = number_format($offer->required_amount,2);
  $confirmMsg = "You are about to invest ৳{$amt} in “{$offer->title}”.\n• The amount is taken from your wallet now\n• Only one investor can fund this offer\n• Returns are not guaranteed; you may lose money";
@endphp
<a href="{{ route('offers') }}" class="text-sm text-indigo-700 hover:underline">← All offers</a>
<div class="grid lg:grid-cols-3 gap-6 mt-3">
  <div class="lg:col-span-2 space-y-4">
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br {{ $th[0] }} {{ $th[1] }} text-white p-6 fade-up">
      <div class="absolute -right-8 -top-8 w-44 h-44 rounded-full bg-white/10"></div><div class="absolute right-6 bottom-3 text-7xl float-slow">{{ $th[2] }}</div>
      <div class="relative"><span class="text-xs bg-white/20 rounded-full px-2.5 py-0.5">{{ ucwords(str_replace('_',' ',$offer->category)) }}</span> <span class="text-xs opacity-80 ml-1">#{{ $offer->code }}</span>
      <h1 class="text-2xl md:text-3xl font-extrabold mt-3 max-w-lg">{{ $offer->title }}</h1></div>
    </div>
    <div class="bg-white border rounded-2xl p-5 fade-up" style="animation-delay:80ms"><h2 class="font-bold mb-2">About this offer</h2><p class="text-slate-700 whitespace-pre-line text-sm leading-relaxed">{{ $offer->description }}</p></div>
    <div class="bg-white border rounded-2xl p-5 fade-up" style="animation-delay:140ms">
      <h2 class="font-bold mb-3">Project details</h2>
      <dl class="grid grid-cols-2 gap-y-3 text-sm">
        <dt class="text-slate-500">Product</dt><dd class="font-medium">{{ $offer->product_name }} × {{ number_format($offer->quantity) }}</dd>
        <dt class="text-slate-500">Total project cost</dt><dd class="font-medium">৳{{ number_format($offer->total_cost,2) }}</dd>
        <dt class="text-slate-500">Company contribution</dt><dd class="font-medium">৳{{ number_format($offer->business_contribution,2) }}</dd>
        <dt class="text-slate-500">Your share</dt><dd class="font-medium">{{ $share }}% of actual net profit <span class="text-xs text-slate-400">(not guaranteed)</span></dd>
        <dt class="text-slate-500">Est. settlement</dt><dd class="font-medium">{{ $offer->estimated_settlement_date ? $offer->estimated_settlement_date->format('d M Y') : 'To be announced' }}</dd>
      </dl>
    </div>
    <div class="bg-white border rounded-2xl p-5 fade-up" style="animation-delay:200ms">
      <h2 class="font-bold mb-3">How it works</h2>
      <ol class="space-y-3 text-sm">
        @foreach([['1','You invest','The required amount is reserved from your wallet and the offer closes to everyone else.'],['2','We run the order','Progress updates are posted here and you get notified.'],['3','Verified settlement','After delivery, payment and costs are verified, principal plus your share of profit (or minus your share of loss) is credited to your wallet.']] as [$n,$t,$d])
        <li class="flex gap-3"><span class="w-7 h-7 shrink-0 rounded-full bg-indigo-600 text-white text-xs font-bold flex items-center justify-center">{{ $n }}</span><div><b>{{ $t }}</b><div class="text-slate-500">{{ $d }}</div></div></li>
        @endforeach
      </ol>
    </div>
    <div class="bg-amber-50 border border-amber-200 rounded-2xl p-5 text-sm text-amber-900 fade-up" style="animation-delay:260ms"><div class="font-bold mb-1">⚠ Risk disclosure</div><p class="whitespace-pre-line">{{ $offer->risk_disclosure }}</p></div>
  </div>

  <aside class="lg:sticky lg:top-20 self-start bg-white border rounded-2xl p-5 space-y-3 shadow-lg fade-up" style="animation-delay:120ms">
    <div class="text-xs uppercase tracking-wide text-slate-500">Required investment</div>
    <div class="text-4xl font-extrabold">৳{{ $amt }}</div>
    <div class="rounded-xl bg-amber-50 text-amber-800 p-3 text-sm">⏰ Closes <b>{{ $offer->deadline_at->timezone('Asia/Dhaka')->format('d M Y, h:i A') }}</b><div class="font-bold" data-deadline="{{ $offer->deadline_at->toIso8601String() }}"></div></div>
    <div class="text-sm flex justify-between"><span class="text-slate-500">Your balance</span><b>৳{{ number_format($balance,2) }}</b></div>
    @if($mine)
      <a class="block text-center bg-green-600 text-white rounded-xl py-2.5 text-sm font-semibold" href="{{ route('investments.show',$mine) }}">✔ You funded this. View investment</a>
    @elseif($open)
      @if(! auth()->user()->investor_verified_at)<div class="text-xs p-2.5 bg-amber-50 rounded-lg text-amber-900">Identity verification is required before investing.</div>@endif
      @if(bccomp((string)$balance,(string)$offer->required_amount,2) < 0)<div class="text-xs p-2.5 bg-red-50 rounded-lg text-red-700">Your balance is lower than the required amount. <a class="underline font-medium" href="{{ route('wallet') }}">Add funds</a></div>@endif
      <form method="POST" action="{{ route('offers.invest',$offer) }}" data-confirm="{{ $confirmMsg }}" data-confirm-title="Confirm your investment" data-confirm-btn="Yes, invest ৳{{ $amt }}" class="space-y-3">@csrf
        <input type="hidden" name="token" value="{{ \Illuminate\Support\Str::uuid() }}">
        <label class="flex gap-2 text-xs text-slate-600"><input type="checkbox" name="agree" value="1" required class="mt-0.5 accent-indigo-600"> I have read the risk disclosure. I understand returns are not guaranteed and I may lose money.</label>
        <button class="btn-shine w-full bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-700 hover:to-violet-700 text-white py-3 rounded-xl font-semibold shadow-lg transition active:scale-95">Invest ৳{{ $amt }}</button>
      </form>
      <p class="text-[11px] text-slate-400 text-center">🔒 Only one investor can fund this offer. You get an agreement slip right after.</p>
    @else <div class="p-3 bg-red-50 text-red-700 rounded-xl text-sm text-center">This offer is no longer available.</div> @endif
  </aside>
</div>
@endsection
