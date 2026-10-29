@extends('layouts.app')
@section('content')
@include('partials.help', ['title' => 'শুরু করবেন যেভাবে', 'lines' => [
'<b>১.</b> Wallet এ গিয়ে ব্যাংক বা bKash এ টাকা পাঠিয়ে ডিপোজিট জমা দিন। স্টাফ যাচাই করলে ব্যালেন্সে যোগ হবে।',
'<b>২.</b> উপরে "Verification pending" দেখালে স্টাফের যাচাইয়ের অপেক্ষা করুন। যাচাই ছাড়া invest ও withdraw বন্ধ।',
'<b>৩.</b> Offers থেকে একটি বেছে Details দেখুন, ঝুঁকির লেখা পড়ে Invest চাপুন। একটি অফারে শুধু একজনই invest করতে পারে।',
'<b>৪.</b> My Investments এ কাজের অগ্রগতি দেখুন। কাজ শেষে হিসাব অনুমোদন হলে টাকা Wallet এ আসবে, তখন Withdraw দিতে পারবেন।',
'<b>Available balance</b> = খরচ বা withdraw করার মতো টাকা। <b>Invested</b> = চলমান বিনিয়োগে আটকে থাকা টাকা। <b>Reserved</b> = withdraw এর জন্য আলাদা করা টাকা। <b>Realized profit/loss</b> = শুধু শেষ হওয়া বিনিয়োগের প্রকৃত ফল (আনুমানিক নয়)।'
]])
<div class="mb-6"><h1 class="text-2xl font-bold">Welcome, {{ auth()->user()->name }}</h1><p class="text-sm text-slate-500">Your wallet and investments at a glance.</p></div>

@if(! auth()->user()->investor_verified_at)
<div class="mb-6 p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 text-sm">
  <b>Verification pending.</b> You can deposit now. Investing and withdrawing unlock after our staff verify your identity.
</div>
@endif

<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-8">
  <div class="col-span-2 lg:col-span-1 bg-indigo-600 text-white rounded-xl p-4 shadow"><div class="text-xs opacity-80">Available balance</div><div class="text-2xl font-bold">৳{{ number_format($available,2) }}</div>
    <a href="{{ route('wallet') }}" class="inline-block mt-2 text-xs bg-white/20 rounded px-2 py-1">Deposit / Withdraw</a></div>
  @foreach([['Invested (not settled)',number_format($invested,2),'৳'],['Reserved for withdrawal',number_format($reservedWd,2),'৳'],['Pending deposits',number_format($pendingDeposits,2),'৳']] as [$l,$v,$c])
  <div class="bg-white rounded-xl border p-4"><div class="text-xs text-slate-500">{{ $l }}</div><div class="text-xl font-bold">{{ $c }}{{ $v }}</div></div>
  @endforeach
  <div class="bg-white rounded-xl border p-4"><div class="text-xs text-slate-500">Active investments</div><div class="text-xl font-bold">{{ $active }}</div></div>
  <div class="bg-white rounded-xl border p-4"><div class="text-xs text-slate-500">Completed</div><div class="text-xl font-bold">{{ $completed }}</div></div>
  @php $net = (float)$realized->p - (float)$realized->l; @endphp
  <div class="bg-white rounded-xl border p-4"><div class="text-xs text-slate-500">Realized profit / loss</div><div class="text-xl font-bold {{ $net < 0 ? 'text-red-700' : 'text-green-700' }}">{{ $net < 0 ? '−' : '+' }}৳{{ number_format(abs($net),2) }}</div></div>
</div>

<div class="flex items-center justify-between mb-3"><h2 class="font-semibold text-lg">Open offers</h2><a href="{{ route('offers') }}" class="text-sm text-indigo-700">See all →</a></div>
<div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
  @forelse($offers as $o) @include('partials.offer-card') @empty
  <div class="sm:col-span-3 bg-white border border-dashed rounded-xl p-8 text-center text-slate-500">No open offers right now. New offers will appear here and you will be notified.</div>
  @endforelse
</div>

<div class="flex items-center justify-between mb-3"><h2 class="font-semibold text-lg">Recent investments</h2><a href="{{ route('investments') }}" class="text-sm text-indigo-700">See all →</a></div>
<div class="bg-white border rounded-xl divide-y">
  @forelse($recent as $i)
  <a href="{{ route('investments.show',$i) }}" class="flex items-center justify-between p-3 hover:bg-slate-50">
    <div><div class="font-medium">{{ $i->offer->title }}</div><div class="text-xs text-slate-500">INV-{{ str_pad($i->id,6,'0',STR_PAD_LEFT) }} · {{ $i->confirmed_at?->timezone('Asia/Dhaka')->format('d M Y') }}</div></div>
    <div class="text-right"><div class="font-semibold">৳{{ number_format($i->amount,2) }}</div>@include('partials.badge',['s'=>$i->status,'kind'=>'investment'])</div>
  </a>
  @empty <div class="p-6 text-center text-slate-500 text-sm">You have not invested yet.</div> @endforelse
</div>
@endsection
