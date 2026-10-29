@extends('layouts.app')
@section('content')
<form method="POST" action="{{ route('login') }}" class="max-w-sm mx-auto bg-white p-6 rounded shadow space-y-3">@csrf
  <h1 class="text-xl font-bold">Login</h1>
  <label class="block text-sm">Email<input name="email" type="email" value="{{ old('email') }}" required class="w-full border rounded p-2"></label>
  <label class="block text-sm">Password<input name="password" type="password" required class="w-full border rounded p-2"></label>
  <label class="text-sm"><input type="checkbox" name="remember"> Remember me</label>
  <button class="w-full bg-indigo-600 text-white py-2 rounded">Login</button>
</form>
@endsection
