@extends('layouts.app')
@section('content')
<div class="py-12 text-center max-w-2xl mx-auto">
  <h1 class="text-3xl font-bold mb-3">Order-based financing for a bottle supply business</h1>
  <p class="text-slate-600 mb-4">Verified investors can fund individual orders. Each offer is funded by exactly one investor. Results depend on actual project outcome; capital loss is possible and no return is guaranteed.</p>
  <a href="{{ route('register') }}" class="bg-indigo-600 text-white px-5 py-2 rounded">Create account</a>
  <p class="text-xs text-slate-400 mt-6">Pre-launch notice: public fundraising requires legal and regulatory approval in Bangladesh.</p>
</div>
@endsection
