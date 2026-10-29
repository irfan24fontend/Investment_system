@extends('layouts.app')
@section('content')
<h1 class="text-xl font-bold mb-3">Withdrawals</h1>
<div class="bg-white rounded-xl border overflow-x-auto"><table class="w-full text-sm"><tr class="bg-slate-100 text-left"><th class="p-2">Investor</th><th>Amount</th><th>Pay to</th><th>Status</th><th>Action</th></tr>
@foreach($rows as $w)<tr class="border-t align-top"><td class="p-2">{{ $w->user->name }}<div class="text-xs text-slate-500">{{ $w->user->phone }}</div></td><td class="font-semibold">৳{{ number_format($w->amount,2) }}</td>
<td><b>{{ $w->bank_name ?? strtoupper($w->method) }}</b><div>{{ $w->account_name }}</div><div class="font-mono">{{ $w->destination }}</div>@if($w->account_name && strcasecmp(trim($w->account_name), trim($w->user->name)) !== 0)<div class="text-xs text-amber-700">⚠ Name differs from investor name</div>@endif</td>
<td>{{ $w->status }} <div class="text-xs text-slate-500">{{ $w->payment_reference }} {{ $w->admin_note }}</div></td>
<td>@if($w->status==='pending')<div class="flex flex-wrap gap-1"><form method="POST" action="{{ route('admin.withdrawals.complete',$w) }}" data-confirm="Money already sent to this account?" class="flex gap-1">@csrf<input name="reference" placeholder="Payment ref" required class="border p-1 rounded"><button class="bg-green-600 text-white px-2 py-1 rounded">Mark paid</button></form>
<form method="POST" action="{{ route('admin.withdrawals.reject',$w) }}" class="flex gap-1">@csrf<input name="reason" placeholder="Reason" required class="border p-1 rounded"><button class="bg-red-600 text-white px-2 py-1 rounded">Reject</button></form></div>@endif</td></tr>@endforeach</table></div>{{ $rows->links() }}
@endsection
