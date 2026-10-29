@extends('layouts.app')
@section('content')
<h1 class="text-xl font-bold mb-3">Notifications</h1>
@forelse($rows as $n)<div class="bg-white p-3 rounded shadow mb-2"><b>{{ $n->title }}</b><div class="text-sm">{{ $n->body }}</div><div class="text-xs text-slate-400">{{ $n->created_at->timezone('Asia/Dhaka')->format('d M Y H:i') }}</div></div>@empty<p class="text-slate-500">Nothing yet.</p>@endforelse
{{ $rows->links() }}
@endsection
