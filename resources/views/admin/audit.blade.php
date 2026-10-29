@extends('layouts.app')
@section('content')
@include('partials.help', ['title' => 'অডিট লগ', 'lines' => [
'কে, কখন, কোন গুরুত্বপূর্ণ কাজ করেছে (deposit approve, settlement, offer publish ইত্যাদি) এখানে লেখা থাকে। এটি মুছে ফেলা বা বদলানো যায় না।'
]])
<h1 class="text-xl font-bold mb-3">Audit log</h1>
<div class="bg-white rounded shadow overflow-x-auto"><table class="w-full text-xs">@foreach($rows as $r)<tr class="border-t"><td class="p-2">{{ $r->created_at }}</td><td>user {{ $r->user_id }}</td><td>{{ $r->action }}</td><td>{{ $r->subject_type }} {{ $r->subject_id }}</td><td>{{ $r->meta }}</td></tr>@endforeach</table></div>{{ $rows->links() }}
@endsection
