@extends('layouts.app')
@section('content')
@include('partials.help', ['title' => 'অফার কীভাবে কাজ করে', 'lines' => [
'প্রতিটি অফার <b>শুধু একজন</b> ইনভেস্টর নিতে পারে। আপনি invest করলে অন্যরা অফারটি আর দেখবে না। অন্য কেউ আগে নিলে অফারটি এই তালিকা থেকে চলে যাবে।',
'Deadline পার হলে আর invest করা যায় না। লাভের নিশ্চয়তা নেই: প্রকৃত ফল অনুযায়ী লাভ বা লোকসান হতে পারে।'
]])
<h1 class="text-2xl font-bold mb-4">Available offers</h1>
<form class="flex flex-wrap gap-2 mb-5"><input name="q" value="{{ request('q') }}" placeholder="Search offers…" class="border rounded-lg px-3 py-2 text-sm flex-1 min-w-[12rem]">
<select name="category" class="border rounded-lg px-3 py-2 text-sm"><option value="">All categories</option>@foreach(['order_financing','stock_financing','bulk_purchase_financing'] as $c)<option value="{{ $c }}" @selected(request('category')===$c)>{{ ucwords(str_replace('_',' ',$c)) }}</option>@endforeach</select>
<button class="px-4 bg-slate-800 text-white rounded-lg text-sm">Filter</button></form>
<div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
@forelse($offers as $o) @include('partials.offer-card') @empty
<div class="sm:col-span-3 bg-white border border-dashed rounded-xl p-10 text-center text-slate-500">No open offers match. Each offer can be funded by one investor only, so funded offers disappear.</div>
@endforelse</div>
<div class="mt-4">{{ $offers->links() }}</div>
@endsection
