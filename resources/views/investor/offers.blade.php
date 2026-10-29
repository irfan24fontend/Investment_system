@extends('layouts.app')
@section('content')
<div class="flex flex-wrap items-end justify-between gap-3 mb-5 fade-up">
  <div><h1 class="text-2xl font-extrabold">Available offers</h1><p class="text-sm text-slate-500">{{ $offers->total() }} open · each offer is funded by one investor only</p></div>
  <form class="flex flex-wrap gap-2"><input name="q" value="{{ request('q') }}" placeholder="🔎 Search offers…" class="border rounded-xl px-3 py-2 text-sm min-w-[12rem] focus:ring-2 focus:ring-indigo-300 outline-none">
  <select name="category" class="border rounded-xl px-3 py-2 text-sm"><option value="">All categories</option>@foreach(['order_financing','stock_financing','bulk_purchase_financing'] as $c)<option value="{{ $c }}" @selected(request('category')===$c)>{{ ucwords(str_replace('_',' ',$c)) }}</option>@endforeach</select>
  <button class="px-4 bg-slate-900 text-white rounded-xl text-sm hover:bg-slate-700 transition">Filter</button></form>
</div>
<div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
@forelse($offers as $o) @include('partials.offer-card', ['idx' => $loop->index]) @empty
<div class="sm:col-span-3 bg-white border border-dashed rounded-2xl p-12 text-center text-slate-500"><div class="text-5xl mb-2 float-slow">🔍</div>No open offers match. Funded offers disappear automatically.</div>
@endforelse</div>
<div class="mt-5">{{ $offers->links() }}</div>
@endsection
