@extends('layouts.app')
@section('content')
@include('partials.help', ['title' => 'রিপোর্ট কীভাবে পড়বেন', 'lines' => [
'<b>Ledger reconciliation = Balanced</b> মানে সব টাকার হিসাব মিলছে। <b>MISMATCH</b> দেখালে কোথাও ভুল আছে: নতুন লেনদেন বন্ধ রেখে ডেভেলপারকে জানান।',
'<b>Total deposited</b> = এ পর্যন্ত approve হওয়া মোট ডিপোজিট।',
'<b>Investor wallets (liability)</b> = ইনভেস্টরদের যে টাকা আপনি ফেরত দিতে বাধ্য। এটি আপনার নিজের টাকা নয়।',
'<b>Export ledger CSV</b> = সব লেনদেনের ফাইল ডাউনলোড (audit এ লেখা থাকে)।'
]])
<h1 class="text-xl font-bold mb-3">Financial report</h1>
<div class="grid md:grid-cols-3 gap-4 text-sm">
 <div class="bg-white p-4 rounded shadow">Total deposited<div class="text-xl font-bold">৳{{ number_format($deposited,2) }}</div></div>
 <div class="bg-white p-4 rounded shadow">Investor wallets (liability)<div class="text-xl font-bold">৳{{ number_format($byType['wallet'] ?? 0,2) }}</div></div>
 <div class="bg-white p-4 rounded shadow">Reserved withdrawals<div class="text-xl font-bold">৳{{ number_format($byType['withdrawal_reserved'] ?? 0,2) }}</div></div>
 <div class="bg-white p-4 rounded shadow">Active investments (obligation)<div class="text-xl font-bold">৳{{ number_format($activeInvest,2) }}</div></div>
 <div class="bg-white p-4 rounded shadow">Realized net (projects)<div class="text-xl font-bold">৳{{ number_format($settled->net,2) }}</div><div class="text-xs">Investor profit ৳{{ number_format($settled->p,2) }} · investor loss ৳{{ number_format($settled->l,2) }}</div></div>
 <div class="bg-white p-4 rounded shadow">Expired / cancelled offers<div class="text-xl font-bold">{{ $expired }} / {{ $cancelled }}</div></div>
 <div class="bg-white p-4 rounded shadow">Ledger reconciliation<div class="text-xl font-bold {{ $reconcile==='0.00'?'text-green-700':'text-red-700' }}">{{ $reconcile==='0.00'?'Balanced':'MISMATCH ৳'.$reconcile }}</div></div>
</div>
<a class="inline-block mt-4 bg-slate-800 text-white px-3 py-2 rounded" href="{{ route('admin.reports.ledger') }}">Export ledger CSV</a>
@endsection
