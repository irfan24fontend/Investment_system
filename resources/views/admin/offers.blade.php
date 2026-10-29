@extends('layouts.app')
@section('content')
@include('partials.help', ['title' => 'এই পেজে কী করবেন ও কোন বাটনে কী হবে', 'lines' => [
'<b>১. Save draft</b> চাপলে অফার তৈরি হয়, কিন্তু ইনভেস্টররা এখনও দেখে না।',
'<b>২. Publish</b> চাপলে অফার সব verified ইনভেস্টরের কাছে দেখা যায়। Deadline পার হলে আর কেউ invest করতে পারে না।',
'<b>৩.</b> একজন ইনভেস্টর invest করলে অফারের status হয় <b>funded</b> এবং অন্য কেউ আর অফারটি দেখে না। তখনই <b>Progress / Complete</b> বাটন আসে।',
'<b>Pause</b> = সাময়িক লুকানো (পরে আবার Publish করা যায়)। <b>Cancel</b> = বাতিল। <b>Edit</b> = শুধু draft বা paused অফারে। Funded অফার আর বদলানো যায় না।',
'<b>Total cost</b> = (পরিমাণ × ইউনিট দাম) + অন্যান্য খরচ। <b>ইনভেস্টরকে দিতে হবে</b> = Total cost − আপনার নিজের contribution। সিস্টেম নিজেই হিসাব করে।',
'<b>Investor return %</b> = ইনভেস্টরের <u>বিনিয়োগের উপর</u> target লাভ। যেমন ৳10,000 এ 10% = ৳1,000। ইনভেস্টর অফার কার্ডে এই target profit দেখে। এটি গ্যারান্টি নয়: প্রকল্পের প্রকৃত লাভ কম হলে সে কম পায়, লোকসান হলে মূলধনও কমে।'
]])
<h1 class="text-xl font-bold mb-3">Investment offers</h1>
@include('partials.offer-form', ['action' => route('admin.offers.store'), 'button' => 'Save draft', 'offer' => null])
<div class="bg-white rounded shadow overflow-x-auto"><table class="w-full text-sm"><tr class="bg-slate-100 text-left"><th class="p-2">Code</th><th>Title</th><th>Required</th><th>Deadline</th><th>Status</th><th>Actions</th></tr>
@foreach($rows as $o)
@php $btns = match($o->status) { 'draft' => ['published'=>'Publish','cancelled'=>'Cancel'], 'published' => ['paused'=>'Pause','cancelled'=>'Cancel'], 'paused' => ['published'=>'Publish','cancelled'=>'Cancel'], default => [] }; @endphp
<tr class="border-t"><td class="p-2">{{ $o->code }}</td><td>{{ $o->title }}</td><td>৳{{ number_format($o->required_amount,2) }}</td><td>{{ $o->deadline_at->timezone('Asia/Dhaka')->format('d M Y H:i') }}</td><td>{{ $o->status }}</td>
<td class="flex flex-wrap gap-1 py-1">
@if(in_array($o->status,['draft','paused']))<a href="{{ route('admin.offers.edit',$o) }}" class="px-2 border rounded text-indigo-700">Edit</a>@endif
@if(in_array($o->status,['funded','completed']))<a href="{{ route('admin.offers.progress',$o) }}" class="px-2 border rounded text-green-700">Progress / Complete</a>@endif
@foreach($btns as $to=>$lbl)<form method="POST" action="{{ route('admin.offers.status',$o) }}" onsubmit="return confirm('{{ $lbl }} this offer?')">@csrf<input type="hidden" name="to" value="{{ $to }}"><button class="px-2 border rounded {{ $to==='cancelled'?'text-red-700':'' }}">{{ $lbl }}</button></form>@endforeach
@if($o->status==='published')<span class="text-xs text-slate-500 self-center">To edit: Pause first</span>@endif
</td></tr>@endforeach</table></div>{{ $rows->links() }}
@endsection
