@extends('layouts.app')
@section('content')
<h1 class="text-xl font-bold mb-3">Deposits</h1>
<div class="bg-white rounded-xl border overflow-x-auto"><table class="w-full text-sm"><tr class="bg-slate-100 text-left"><th class="p-2">User</th><th>Method</th><th>Amount</th><th>Ref / From</th><th>Slip</th><th>Status</th><th>Action</th></tr>
@foreach($rows as $d)<tr class="border-t align-top"><td class="p-2">{{ $d->user->name }}<div class="text-xs text-slate-500">{{ $d->user->phone }}</div></td><td>{{ strtoupper($d->method) }}</td><td>৳{{ number_format($d->amount,2) }}</td>
<td>{{ $d->reference }}@if($d->sender_number)<div class="text-xs text-slate-500">from {{ $d->sender_number }}</div>@endif<div class="text-xs text-slate-400">{{ $d->created_at->timezone('Asia/Dhaka')->format('d M H:i') }}</div></td>
<td>@if($d->receipt_path)<a class="text-indigo-700 underline" target="_blank" href="{{ route('admin.deposits.receipt',$d) }}">View</a>@endif</td><td>{{ $d->status }} <span class="text-xs text-slate-500">{{ $d->rejection_reason }}</span></td>
<td>@if($d->status==='pending')<div class="flex flex-wrap gap-1"><form method="POST" action="{{ route('admin.deposits.approve',$d) }}" data-confirm="Money is in our account and TrxID/slip/amount match? Credit wallet?">@csrf<button class="bg-green-600 text-white px-2 py-1 rounded">Approve</button></form>
<form method="POST" action="{{ route('admin.deposits.reject',$d) }}" class="flex gap-1">@csrf<input name="reason" placeholder="Reason" required class="border p-1 rounded"><button class="bg-red-600 text-white px-2 py-1 rounded">Reject</button></form></div>@endif</td></tr>@endforeach</table></div>{{ $rows->links() }}
@endsection
