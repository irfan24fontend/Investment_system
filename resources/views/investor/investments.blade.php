@extends('layouts.app')
@section('content')
<h1 class="text-2xl font-extrabold mb-4 fade-up">My investments</h1>
<div class="space-y-3">
@forelse($rows as $k=>$i)
<div class="bg-white border rounded-2xl p-4 flex flex-wrap items-center gap-3 justify-between hover:shadow-md hover:-translate-y-0.5 transition fade-up" style="animation-delay: {{ $k*70 }}ms">
  <div><div class="font-semibold">{{ $i->offer->title }}</div>
    <div class="text-xs text-slate-500">INV-{{ str_pad($i->id,6,'0',STR_PAD_LEFT) }} · {{ $i->confirmed_at?->timezone('Asia/Dhaka')->format('d M Y, h:i A') }}</div></div>
  <div class="text-right"><div class="font-bold">৳{{ number_format($i->amount,2) }}</div>@include('partials.badge',['s'=>$i->status,'kind'=>'investment'])@if($i->settlement && $i->settlement->status==='posted')<div class="text-xs mt-1 {{ $i->settlement->investor_loss>0 ? 'text-red-700' : 'text-green-700' }}">{{ $i->settlement->investor_loss>0 ? 'Loss −৳'.number_format($i->settlement->investor_loss,2) : 'Profit +৳'.number_format($i->settlement->investor_profit,2) }} · Paid ৳{{ number_format($i->settlement->payout,2) }}</div>@endif</div>
  <div class="flex gap-2 w-full sm:w-auto">
    <a href="{{ route('investments.show',$i) }}" class="px-3 py-1.5 border rounded-lg text-sm hover:bg-slate-50 transition">Details</a>
    <a href="{{ route('investments.receipt',$i) }}" target="_blank" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm transition">Agreement slip</a>
  </div>
</div>
@empty <div class="bg-white border border-dashed rounded-2xl p-12 text-center text-slate-500"><div class="text-5xl mb-2 float-slow">🌱</div>No investments yet. <a class="text-indigo-700 underline" href="{{ route('offers') }}">Browse offers</a></div>
@endforelse</div>
<div class="mt-4">{{ $rows->links() }}</div>
@endsection
