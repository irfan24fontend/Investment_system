<!doctype html>
<html lang="bn">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $title ?? config('app.name', 'Bottle Invest') }}</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com/3.4.17"></script>
<script>tailwind.config={theme:{extend:{fontFamily:{sans:['Inter','Hind Siliguri','system-ui','sans-serif']}}}}</script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>
<style>[x-cloak]{display:none!important}</style>
</head>
<body x-data="{open:false}" @keydown.escape.window="open=false" x-effect="document.body.classList.toggle('overflow-hidden', open)" class="bg-slate-50 text-slate-800 antialiased min-h-screen flex flex-col">
@php
  $u = auth()->user();
  $unread = $u ? \App\Models\UserNotice::where('user_id', $u->id)->whereNull('read_at')->count() : 0;
  if ($u && $u->role === 'investor') $links = [['dashboard','Dashboard'],['offers','Offers'],['investments','My Investments'],['wallet','Wallet']];
  elseif ($u) {
    $links = [['admin.dashboard','Admin']];
    if ($u->hasRole('super_admin')) { $links[] = ['admin.offers','Offers']; $links[] = ['admin.audit','Audit']; }
    if ($u->hasRole('super_admin','finance_manager')) { array_push($links, ['admin.investors','Investors'],['admin.deposits','Deposits'],['admin.withdrawals','Withdrawals'],['admin.settlements','Settlements'],['admin.reports','Reports']); }
    if ($u->hasRole('super_admin','order_manager')) $links[] = ['admin.orders','Orders'];
  } else $links = [];
  if ($u) $links[] = ['help','Guide'];
  $bars = 'M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5';
  $icons = [
    'dashboard' => 'M2.25 12l8.954-8.955a1.126 1.126 0 011.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25',
    'offers' => 'M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z',
    'investments' => 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z',
    'wallet' => 'M21 12a2.25 2.25 0 00-2.25-2.25H15a3 3 0 11-6 0H5.25A2.25 2.25 0 003 12m18 0v6a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 9m18 0V6a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 6v3',
    'help' => 'M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z',
    'bell' => 'M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0',
    'admin.dashboard' => 'M2.25 12l8.954-8.955a1.126 1.126 0 011.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25',
  ];
@endphp
<header class="bg-white border-b sticky top-0 z-40">
  <div class="max-w-6xl mx-auto px-4 h-14 flex items-center gap-3 md:gap-6">
    @auth
    <button type="button" @click="open=true" class="md:hidden -ml-2 p-2 rounded-lg text-slate-700 hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500" aria-label="Open menu" aria-controls="mobile-menu" :aria-expanded="open.toString()">
      <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $bars }}"/></svg>
    </button>
    @endauth
    <a href="{{ route('home') }}" class="font-bold text-indigo-700 text-lg tracking-tight">{{ config('app.name','Bottle Invest') }}</a>
    <nav class="hidden md:flex items-center gap-5 text-sm">
      @foreach($links as [$r,$l])<a href="{{ route($r) }}" class="{{ request()->routeIs($r) ? 'text-indigo-700 font-semibold' : 'text-slate-600 hover:text-slate-900' }}">{{ $l }}</a>@endforeach
    </nav>
    <div class="ml-auto flex items-center gap-3 text-sm">
      @auth
        @if($u->role==='investor')
        <a href="{{ route('notices') }}" class="relative p-2 rounded-lg text-slate-600 hover:bg-slate-100" aria-label="Notifications">
          <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $icons['bell'] }}"/></svg>
          @if($unread)<span class="absolute top-0.5 right-0 bg-red-600 text-white text-[10px] leading-none rounded-full px-1.5 py-1">{{ $unread > 99 ? '99+' : $unread }}</span>@endif
        </a>
        @endif
        <form method="POST" action="{{ route('logout') }}" class="hidden md:block">@csrf<button class="text-slate-500 hover:text-slate-800">Logout</button></form>
      @else
        <a href="{{ route('login') }}" class="text-slate-600">Login</a><a href="{{ route('register') }}" class="bg-indigo-600 text-white px-3 py-1.5 rounded-lg">Register</a>
      @endauth
    </div>
  </div>

</header>
  @auth
  {{-- Mobile slide-in menu --}}
  <div class="md:hidden" id="mobile-menu">
    <div x-show="open" x-cloak x-transition.opacity.duration.200ms @click="open=false" class="fixed inset-0 z-50 bg-slate-900/50" aria-hidden="true"></div>
    <aside x-show="open" x-cloak role="dialog" aria-modal="true" aria-label="Main menu"
           x-transition:enter="transition ease-out duration-200" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
           x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full"
           class="fixed inset-y-0 left-0 z-50 w-72 max-w-[85%] bg-white shadow-2xl flex flex-col">
      <div class="flex items-center justify-between px-4 h-14 border-b">
        <span class="font-bold text-indigo-700 text-lg">{{ config('app.name','Bottle Invest') }}</span>
        <button type="button" @click="open=false" class="p-2 -mr-2 rounded-lg text-slate-500 hover:bg-slate-100" aria-label="Close menu">
          <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </div>
      <div class="px-4 py-4 bg-slate-50 border-b flex items-center gap-3">
        <div class="w-11 h-11 rounded-full bg-indigo-600 text-white flex items-center justify-center font-semibold text-lg">{{ mb_strtoupper(mb_substr($u->name, 0, 1)) }}</div>
        <div class="min-w-0">
          <div class="font-semibold text-slate-900 truncate">{{ $u->name }}</div>
          <div class="text-xs text-slate-500 capitalize">{{ str_replace('_',' ',$u->role) }}</div>
          @if($u->role==='investor')
            @if($u->investor_verified_at)<span class="inline-block mt-1 text-[11px] px-2 py-0.5 rounded-full bg-green-100 text-green-800">✔ Verified</span>
            @else<span class="inline-block mt-1 text-[11px] px-2 py-0.5 rounded-full bg-amber-100 text-amber-800">Verification pending</span>@endif
          @endif
        </div>
      </div>
      <nav class="flex-1 overflow-y-auto py-2" aria-label="Mobile">
        @foreach($links as [$r,$l])
          @php $on = request()->routeIs($r); @endphp
          <a href="{{ route($r) }}" @if($on) aria-current="page" @endif class="flex items-center gap-3 px-4 py-3 text-[15px] border-l-4 {{ $on ? 'border-indigo-600 bg-indigo-50 text-indigo-700 font-semibold' : 'border-transparent text-slate-700 hover:bg-slate-50' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $icons[$r] ?? $bars }}"/></svg>{{ $l }}
          </a>
        @endforeach
        @if($u->role==='investor')
          <a href="{{ route('notices') }}" class="flex items-center gap-3 px-4 py-3 text-[15px] border-l-4 border-transparent text-slate-700 hover:bg-slate-50">
            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $icons['bell'] }}"/></svg>Notifications
            @if($unread)<span class="ml-auto bg-red-600 text-white text-xs rounded-full px-2 py-0.5">{{ $unread }}</span>@endif
          </a>
        @endif
      </nav>
      <form method="POST" action="{{ route('logout') }}" class="border-t p-3">@csrf
        <button class="w-full flex items-center justify-center gap-2 py-2.5 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50 text-sm font-medium">
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"/></svg>Logout
        </button>
      </form>
    </aside>
  </div>
  @endauth

<main class="max-w-6xl w-full mx-auto px-4 py-6 flex-1">
  @if(session('ok'))<div class="mb-4 p-3 rounded-lg bg-green-50 border border-green-200 text-green-800 text-sm" role="status">✔ {{ session('ok') }}</div>@endif
  @if($errors->any())<div class="mb-4 p-3 rounded-lg bg-red-50 border border-red-200 text-red-800 text-sm" role="alert">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
  @yield('content')
</main>
<footer class="border-t bg-white text-xs text-slate-500 py-4 text-center px-4">
  এই প্ল্যাটফর্মের বিনিয়োগে লাভের নিশ্চয়তা নেই; প্রকৃত ফলাফল অনুযায়ী লাভ বা লোকসান হতে পারে।
</footer>
<script>
document.querySelectorAll('[data-deadline]').forEach(function(el){
  function tick(){var ms=new Date(el.dataset.deadline)-new Date();
    if(ms<=0){el.textContent='Closed';return;}
    var d=Math.floor(ms/864e5),h=Math.floor(ms%864e5/36e5),m=Math.floor(ms%36e5/6e4);
    el.textContent=(d?d+'d ':'')+h+'h '+m+'m left';}
  tick();setInterval(tick,30000);});
</script>
</body></html>
