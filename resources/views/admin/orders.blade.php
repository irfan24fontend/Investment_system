@extends('layouts.app')
@section('content')
@include('partials.help', ['title' => 'অর্ডার পেজ: কী করবেন', 'lines' => [
'Order Manager এখানে অর্ডারের পণ্য, ক্রয়খরচ, পরিবহন ইত্যাদি লেখে। এই রোল ইনভেস্টরের টাকা বা ওয়ালেট ছুঁতে পারে না।',
'<b>Link to offer</b> = অর্ডারটি কোন offer এর টাকায় চলবে। ইনভেস্টর invest করলে অর্ডারটি নিজে নিজে <b>financing_secured</b> হয়ে যায়।',
'<b>procurement / ready / delivered</b> দেওয়া যাবে শুধু অফার funded হলে (অর্থাৎ ইনভেস্টর টাকা দিলে)।',
'কাজ শেষ হলে <b>financial_verification</b> দিন। এতে Finance বা Super admin সেটেলমেন্ট করতে পারবে। অর্ডার বাতিল হলে <b>cancelled</b> দিন।',
'<b>settlement_pending</b> ও <b>settled</b> শুধু Finance নিয়ন্ত্রণ করে, এখানে বদলানো যায় না।',
'যে offer এ ইনভেস্টর আগেই invest করেছে সেটি Link তালিকায় আসে না। সেক্ষেত্রে Super admin: Offers → Progress / Complete।'
]])
<h1 class="text-xl font-bold mb-3">Orders</h1>
<form method="POST" action="{{ route('admin.orders.store') }}" class="bg-white p-4 rounded shadow grid md:grid-cols-4 gap-2 mb-6">@csrf
@foreach([['code','Order ID','text'],['customer_ref','Customer ref','text'],['supplier','Supplier','text'],['product_name','Product','text'],['quantity','Qty','number'],['purchase_cost','Purchase cost','number'],['transport_cost','Transport','number'],['other_cost','Other cost','number'],['sale_price','Total sale price ৳ (revenue, Complete এ নিজে বসে যায়)','number'],['delivery_destination','Destination','text'],['expected_delivery_date','Expected delivery','date']] as [$n,$l,$t])
<label class="text-xs">{{ $l }}<input name="{{ $n }}" type="{{ $t }}" step="0.01" value="{{ old($n) }}" class="w-full border rounded p-2 text-sm"></label>@endforeach
<label class="text-xs">Link to offer<select name="offer_id" class="w-full border rounded p-2 text-sm"><option value="">None</option>@foreach($offers as $o)<option value="{{ $o->id }}">{{ $o->code }}</option>@endforeach</select></label>
<button class="bg-indigo-600 text-white rounded py-2">Create</button></form>
<div class="bg-white rounded shadow overflow-x-auto"><table class="w-full text-sm"><tr class="bg-slate-100 text-left"><th class="p-2">Code</th><th>Product</th><th>Offer</th><th>Status</th><th>Update</th></tr>
@foreach($rows as $o)<tr class="border-t"><td class="p-2">{{ $o->code }}</td><td>{{ $o->product_name }} × {{ $o->quantity }}</td><td>{{ $o->offer?->code }}</td><td>{{ $o->status }}</td>
<td><form method="POST" action="{{ route('admin.orders.status',$o) }}" class="flex gap-1">@csrf<select name="to" class="border rounded p-1">@foreach($states as $s)<option>{{ $s }}</option>@endforeach</select><button class="px-2 border rounded">Set</button></form></td></tr>@endforeach</table></div>{{ $rows->links() }}
@endsection
