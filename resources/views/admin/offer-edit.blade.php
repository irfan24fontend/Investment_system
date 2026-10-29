@extends('layouts.app')
@section('content')
<a href="{{ route('admin.offers') }}" class="text-sm text-indigo-700">&larr; Back to offers</a>
<h1 class="text-xl font-bold my-3">Edit offer {{ $offer->code }} <span class="text-sm font-normal text-slate-500">({{ $offer->status }})</span></h1>
@include('partials.offer-form', ['action' => route('admin.offers.update', $offer), 'button' => 'Save changes', 'offer' => $offer])
@endsection
