@extends('layouts.app')
@section('content')
<form method="POST" action="{{ route('register') }}" class="max-w-sm mx-auto bg-white p-6 rounded shadow space-y-3">@csrf
  <h1 class="text-xl font-bold">Investor registration</h1>
  @foreach([['name','Full name','text'],['email','Email','email'],['phone','Mobile (01XXXXXXXXX)','text'],['password','Password (min 10)','password'],['password_confirmation','Confirm password','password']] as [$n,$l,$t])
  <label class="block text-sm">{{ $l }}<input name="{{ $n }}" type="{{ $t }}" value="{{ $t==='password' ? '' : old($n) }}" required class="w-full border rounded p-2"></label>
  @endforeach
  <button class="w-full bg-indigo-600 text-white py-2 rounded">Register</button>
  <p class="text-xs text-slate-500">Identity verification by staff is required before you can invest.</p>
</form>
@endsection
