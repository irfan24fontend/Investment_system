@extends('layouts.app')
@section('content')
@php
  $me = auth()->user(); $fin = $me->hasRole('super_admin','finance_manager'); $adm = $me->hasRole('super_admin');
  $cards = [
    [$fin, 'যাচাই বাকি ইনভেস্টর', $toVerify, 'admin.investors', 'Verify না করলে invest/withdraw করতে পারে না'],
    [$fin, 'পেন্ডিং ডিপোজিট', $pendingDeposits, 'admin.deposits', 'টাকা এসেছে কিনা দেখে Approve করুন'],
    [$fin, 'পেন্ডিং উইথড্র', $pendingWithdrawals, 'admin.withdrawals', 'টাকা পাঠিয়ে Mark paid দিন'],
    [$fin, 'অনুমোদন বাকি সেটেলমেন্ট', $preparedSettlements, 'admin.settlements', 'অন্য Finance user approve করবে'],
    [$adm, 'ড্রাফট অফার', $draftOffers, 'admin.offers', 'Publish না করলে ইনভেস্টর দেখে না'],
    [$adm, 'Funded অফার (কাজ চলছে)', $fundedOffers, 'admin.offers', 'Progress দিন, শেষে Complete করুন'],
  ];
  $labels = ['draft'=>'ড্রাফট','published'=>'পাবলিশড','paused'=>'পজড','funded'=>'Funded','completed'=>'সম্পন্ন','expired'=>'মেয়াদ শেষ','cancelled'=>'বাতিল'];
@endphp
@include('partials.help', ['title' => 'এই পেজ কী', 'lines' => [
  'নিচের কার্ডগুলো আপনার <b>করণীয় কাজ</b>। সংখ্যা 0 এর বেশি হলে কার্ডে চাপ দিয়ে কাজটি করুন।',
  'উপরের মেনুর <b>Guide</b> এ সম্পূর্ণ workflow ও প্রতিটি স্ট্যাটাসের মানে লেখা আছে।'
]])
<div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
@foreach($cards as [$show,$label,$count,$route,$hint])
  @if($show)<a href="{{ route($route) }}" class="bg-white p-4 rounded-xl border shadow-sm hover:shadow-md block"><div class="text-sm text-slate-600">{{ $label }}</div><div class="text-3xl font-bold {{ $count>0 ? 'text-indigo-700' : 'text-slate-400' }}">{{ $count }}</div><div class="text-xs text-slate-500 mt-1">{{ $hint }}</div></a>@endif
@endforeach
@if($fin)<div class="bg-white p-4 rounded-xl border shadow-sm"><div class="text-sm text-slate-600">Ledger হিসাব</div><div class="text-2xl font-bold {{ $reconcile==='0.00'?'text-green-700':'text-red-700' }}">{{ $reconcile==='0.00' ? 'মিলছে ✔' : 'গরমিল ৳'.$reconcile }}</div><div class="text-xs text-slate-500 mt-1">গরমিল দেখালে নতুন লেনদেন বন্ধ রাখুন</div></div>@endif
</div>
@if($adm)
<h2 class="font-semibold mt-6 mb-1">অফারের অবস্থা</h2>
<ul class="text-sm">@forelse($offers as $s=>$c)<li>{{ $labels[$s] ?? $s }}: {{ $c }}</li>@empty<li class="text-slate-500">এখনও কোনো অফার নেই।</li>@endforelse</ul>
@endif
@endsection
