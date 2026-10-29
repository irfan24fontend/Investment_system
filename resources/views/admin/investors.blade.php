@extends('layouts.app')
@section('content')
@include('partials.help', ['title' => 'ইনভেস্টর যাচাই', 'lines' => [
'নতুন ইনভেস্টর register করলে এখানে আসে। তার পরিচয় ও ফোন যাচাই করে <b>Verify</b> চাপুন।',
'Verify না হলে সে ডিপোজিট দিতে পারবে, কিন্তু <b>invest বা withdraw করতে পারবে না</b>।',
'পাশের ঘরের নম্বরটি তার payout bKash নম্বর হিসেবে সেভ হয়।'
]])
<h1 class="text-xl font-bold mb-3">Investors</h1>
<div class="bg-white rounded shadow overflow-x-auto"><table class="w-full text-sm"><tr class="bg-slate-100 text-left"><th class="p-2">Name</th><th>Email</th><th>Phone</th><th>Verified</th><th>Verify + payout number</th></tr>
@foreach($rows as $u)<tr class="border-t"><td class="p-2">{{ $u->name }}</td><td>{{ $u->email }}</td><td>{{ $u->phone }}</td><td>{{ $u->investor_verified_at ? 'Yes' : 'No' }}</td>
<td><form method="POST" action="{{ route('admin.investors.verify',$u) }}" onsubmit="return confirm('Confirm identity checked?')" class="flex gap-1">@csrf<input name="payout_account" value="{{ $u->payout_account ?? $u->phone }}" class="border rounded p-1"><button class="bg-green-600 text-white px-2 rounded">Verify</button></form></td></tr>@endforeach</table></div>{{ $rows->links() }}
@endsection
