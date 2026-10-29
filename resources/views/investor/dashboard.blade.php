@extends('layouts.app')
@section('content')
@php $net = (float)$realized->p - (float)$realized->l; $hour = now('Asia/Dhaka')->hour; $greet = $hour<12 ? 'Good morning' : ($hour<17 ? 'Good afternoon' : 'Good evening'); @endphp

@if(! auth()->user()->investor_verified_at)
<div class="mb-5 p-4 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900 text-sm fade-up">
  <b>⏳ Verification pending.</b> You can deposit now. Investing and withdrawing unlock after our staff verify your identity.
</div>
@endif

<div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-indigo-600 via-violet-600 to-fuchsia-600 text-white p-6 md:p-8 shadow-xl fade-up">
  <div class="absolute -right-10 -top-10 w-56 h-56 rounded-full bg-white/10"></div><div class="absolute right-24 -bottom-16 w-44 h-44 rounded-full bg-white/10"></div>
  <div class="relative flex flex-wrap items-end justify-between gap-5">
    <div>
      <div class="text-sm opacity-90">{{ $greet }}, {{ \Illuminate\Support\Str::of(auth()->user()->name)->before(' ') }} 👋</div>
      <div class="text-xs opacity-75 mt-3">Available balance</div>
      <div class="text-4xl md:text-5xl font-extrabold tracking-tight"><span class="countup" data-v="{{ $available }}" data-prefix="৳">৳{{ number_format($available,2) }}</span></div>
    </div>
    <div class="flex gap-2">
      <a href="{{ route('wallet') }}" class="btn-shine bg-white text-indigo-700 font-semibold rounded-xl px-5 py-2.5 text-sm shadow hover:scale-105 transition">＋ Deposit</a>
      <a href="{{ route('offers') }}" class="bg-white/15 backdrop-blur border border-white/30 rounded-xl px-5 py-2.5 text-sm hover:bg-white/25 transition">Browse offers</a>
    </div>
  </div>
</div>

<div class="grid grid-cols-2 lg:grid-cols-3 gap-3 mt-5 mb-8">
  @php $stats = [
    ['💼','Invested (not settled)',$invested,'৳'],['🏦','Reserved for withdrawal',$reservedWd,'৳'],['⏳','Pending deposits',$pendingDeposits,'৳'],
  ]; @endphp
  @foreach($stats as $k=>[$ic,$l,$v,$p])
  <div class="bg-white rounded-2xl border p-4 hover:shadow-md hover:-translate-y-0.5 transition fade-up" style="animation-delay: {{ 80+$k*70 }}ms">
    <div class="flex items-center justify-between"><div class="text-xs text-slate-500">{{ $l }}</div><span>{{ $ic }}</span></div>
    <div class="text-xl font-bold mt-1"><span class="countup" data-v="{{ $v }}" data-prefix="{{ $p }}">{{ $p }}{{ number_format($v,2) }}</span></div>
  </div>
  @endforeach
  <div class="bg-white rounded-2xl border p-4 hover:shadow-md hover:-translate-y-0.5 transition fade-up" style="animation-delay:290ms"><div class="flex items-center justify-between"><div class="text-xs text-slate-500">Active investments</div><span>🚀</span></div><div class="text-xl font-bold mt-1">{{ $active }}</div></div>
  <div class="bg-white rounded-2xl border p-4 hover:shadow-md hover:-translate-y-0.5 transition fade-up" style="animation-delay:360ms"><div class="flex items-center justify-between"><div class="text-xs text-slate-500">Completed</div><span>✅</span></div><div class="text-xl font-bold mt-1">{{ $completed }}</div></div>
  <div class="bg-white rounded-2xl border p-4 hover:shadow-md hover:-translate-y-0.5 transition fade-up {{ $net>0 ? 'ring-1 ring-green-200' : ($net<0 ? 'ring-1 ring-red-200' : '') }}" style="animation-delay:430ms"><div class="flex items-center justify-between"><div class="text-xs text-slate-500">Realized profit / loss</div><span>{{ $net<0 ? '📉' : '📈' }}</span></div>
    <div class="text-xl font-bold mt-1 {{ $net < 0 ? 'text-red-700' : ($net>0 ? 'text-green-700' : '') }}"><span class="countup" data-v="{{ $net }}" data-prefix="{{ $net>0 ? '+৳' : '৳' }}">{{ $net<0 ? '−' : ($net>0 ? '+' : '') }}৳{{ number_format(abs($net),2) }}</span></div></div>
</div>

<div class="flex items-center justify-between mb-3"><h2 class="font-bold text-lg">🔥 Open offers</h2><a href="{{ route('offers') }}" class="text-sm text-indigo-700 hover:underline">See all →</a></div>
<div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5 mb-10">
  @forelse($offers as $o) @include('partials.offer-card', ['idx' => $loop->index]) @empty
  <div class="sm:col-span-3 bg-white border border-dashed rounded-2xl p-10 text-center text-slate-500"><div class="text-4xl mb-2 float-slow">📭</div>No open offers right now. We notify you when a new one is published.</div>
  @endforelse
</div>

<div class="flex items-center justify-between mb-3"><h2 class="font-bold text-lg">Recent investments</h2><a href="{{ route('investments') }}" class="text-sm text-indigo-700 hover:underline">See all →</a></div>
<div class="bg-white border rounded-2xl divide-y overflow-hidden fade-up">
  @forelse($recent as $inv)
  <a href="{{ route('investments.show',$inv) }}" class="flex items-center justify-between p-3.5 hover:bg-indigo-50/50 transition">
    <div><div class="font-medium">{{ $inv->offer->title }}</div><div class="text-xs text-slate-500">INV-{{ str_pad($inv->id,6,'0',STR_PAD_LEFT) }} · {{ $inv->confirmed_at?->timezone('Asia/Dhaka')->format('d M Y') }}</div></div>
    <div class="text-right"><div class="font-semibold">৳{{ number_format($inv->amount,2) }}</div>@include('partials.badge',['s'=>$inv->status,'kind'=>'investment'])</div>
  </a>
  @empty <div class="p-8 text-center text-slate-500 text-sm"><div class="text-3xl mb-1">🌱</div>You have not invested yet.</div> @endforelse
</div>
@endsection
