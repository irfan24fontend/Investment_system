<!doctype html>
<html lang="bn">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $title ?? config('app.name', 'Bottle Invest') }}</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com/3.4.17"></script>
<script>tailwind.config={theme:{extend:{fontFamily:{sans:['Inter','Hind Siliguri','system-ui','sans-serif']}}}}</script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>
<style>
[x-cloak]{display:none!important}
@keyframes fadeUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:none}}
@keyframes popIn{0%{opacity:0;transform:scale(.92) translateY(8px)}100%{opacity:1;transform:none}}
@keyframes fadeIn{from{opacity:0}to{opacity:1}}
@keyframes toastIn{from{opacity:0;transform:translateX(24px)}to{opacity:1;transform:none}}
@keyframes shake{0%,100%{transform:none}20%,60%{transform:translateX(-5px)}40%,80%{transform:translateX(5px)}}
@keyframes floaty{0%,100%{transform:translateY(0) rotate(-4deg)}50%{transform:translateY(-8px) rotate(4deg)}}
@keyframes shimmer{0%{transform:translateX(-120%)}100%{transform:translateX(220%)}}
@keyframes ring{0%{box-shadow:0 0 0 0 rgba(239,68,68,.6)}100%{box-shadow:0 0 0 10px rgba(239,68,68,0)}}
@keyframes draw{to{stroke-dashoffset:0}}
@keyframes fall{to{transform:translateY(105vh) rotate(720deg);opacity:.85}}
@keyframes spin{to{transform:rotate(360deg)}}
.fade-up{animation:fadeUp .55s cubic-bezier(.2,.7,.2,1) both}
.pop-in{animation:popIn .25s cubic-bezier(.2,.8,.2,1) both}
.fade-in{animation:fadeIn .2s both}
.toast-in{animation:toastIn .35s both}
.shake{animation:shake .4s}
.float-slow{animation:floaty 4s ease-in-out infinite}
.pulse-ring{animation:ring 1.4s infinite}
.btn-shine{position:relative;overflow:hidden}
.btn-shine::after{content:"";position:absolute;inset:0;width:40%;background:linear-gradient(90deg,transparent,rgba(255,255,255,.45),transparent);transform:translateX(-120%);animation:shimmer 2.8s infinite}
.chk-c{stroke-dasharray:151;stroke-dashoffset:151;animation:draw .6s .1s forwards}
.chk-p{stroke-dasharray:40;stroke-dashoffset:40;animation:draw .4s .65s forwards}
.confetti{position:fixed;top:-14px;width:8px;height:14px;z-index:120;animation:fall linear forwards;border-radius:2px}
.spin{display:inline-block;width:14px;height:14px;border:2px solid currentColor;border-right-color:transparent;border-radius:50%;animation:spin .7s linear infinite;vertical-align:-2px;margin-right:6px}
main{animation:fadeIn .35s both}
@media (prefers-reduced-motion: reduce){*{animation:none!important;transition:none!important}}
</style>
</head>
<body class="bg-slate-50 text-slate-800 antialiased min-h-screen flex flex-col">
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
@endphp
<header class="bg-white/90 backdrop-blur border-b sticky top-0 z-40" x-data="{open:false}">
  <div class="max-w-6xl mx-auto px-4 h-14 flex items-center gap-6">
    <a href="{{ route('home') }}" class="font-extrabold text-indigo-700 text-lg tracking-tight">{{ config('app.name','Bottle Invest') }}</a>
    <nav class="hidden md:flex items-center gap-5 text-sm">
      @foreach($links as [$r,$l])<a href="{{ route($r) }}" class="transition {{ request()->routeIs($r) ? 'text-indigo-700 font-semibold' : 'text-slate-600 hover:text-slate-900' }}">{{ $l }}</a>@endforeach
    </nav>
    <div class="ml-auto flex items-center gap-3 text-sm">
      @auth
        @if($u->role==='investor')<a href="{{ route('notices') }}" class="relative text-slate-600" title="Notifications">🔔@if($unread)<span class="absolute -top-2 -right-3 bg-red-600 text-white text-[10px] rounded-full px-1.5 pulse-ring">{{ $unread }}</span>@endif</a>@endif
        <form method="POST" action="{{ route('logout') }}" class="hidden md:block">@csrf<button class="text-slate-500 hover:text-slate-800">Logout</button></form>
        <button @click="open=!open" class="md:hidden p-2 border rounded-lg" aria-label="Menu">☰</button>
      @else
        <a href="{{ route('login') }}" class="text-slate-600">Login</a><a href="{{ route('register') }}" class="bg-indigo-600 text-white px-3 py-1.5 rounded-lg">Register</a>
      @endauth
    </div>
  </div>
  @auth
  <div x-show="open" x-cloak x-transition class="md:hidden border-t bg-white px-4 py-2 space-y-1 text-sm">
    @foreach($links as [$r,$l])<a href="{{ route($r) }}" class="block py-2 {{ request()->routeIs($r) ? 'text-indigo-700 font-semibold' : '' }}">{{ $l }}</a>@endforeach
    @if($u->role==='investor')<a href="{{ route('notices') }}" class="block py-2">Notifications @if($unread)({{ $unread }})@endif</a>@endif
    <form method="POST" action="{{ route('logout') }}">@csrf<button class="py-2 text-slate-500">Logout ({{ $u->name }})</button></form>
  </div>
  @endauth
</header>

@if(session('ok') && ! session('celebrate'))
<div id="toast" class="fixed top-16 right-4 left-4 sm:left-auto z-[90] sm:max-w-sm toast-in">
  <div class="flex gap-3 items-start bg-white border-l-4 border-green-500 shadow-xl rounded-xl p-3 pr-4">
    <span class="text-green-600 text-xl leading-none">✔</span><div class="text-sm flex-1">{{ session('ok') }}</div>
    <button type="button" onclick="this.closest('#toast').remove()" class="text-slate-400" aria-label="Close">✕</button>
  </div>
</div>
<script>setTimeout(function(){var t=document.getElementById('toast');if(t){t.style.transition='opacity .4s';t.style.opacity=0;setTimeout(function(){t.remove()},400)}},5500)</script>
@endif

<main class="max-w-6xl w-full mx-auto px-4 py-6 flex-1">
  @if($errors->any())<div class="mb-4 p-3 rounded-xl bg-red-50 border border-red-200 text-red-800 text-sm shake" role="alert">@foreach($errors->all() as $e)<div>⚠ {{ $e }}</div>@endforeach</div>@endif
  @yield('content')
</main>
<footer class="border-t bg-white text-xs text-slate-500 py-4 text-center px-4">
  এই প্ল্যাটফর্মের বিনিয়োগে লাভের নিশ্চয়তা নেই; প্রকৃত ফলাফল অনুযায়ী লাভ বা লোকসান হতে পারে।
</footer>

{{-- Global confirmation popup: any <form data-confirm="message" data-confirm-title="" data-confirm-btn=""> opens it --}}
<div id="cm" class="fixed inset-0 z-[100] hidden items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="cm-title">
  <div id="cm-bg" class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm fade-in"></div>
  <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6 pop-in">
    <div id="cm-icon" class="w-12 h-12 rounded-full flex items-center justify-center text-2xl mb-3 bg-indigo-100">❓</div>
    <h3 id="cm-title" class="text-lg font-bold"></h3>
    <p id="cm-msg" class="mt-1 text-sm text-slate-600 whitespace-pre-line"></p>
    <div class="mt-5 flex gap-2 justify-end">
      <button id="cm-no" type="button" class="px-4 py-2 rounded-lg border text-sm text-slate-700 hover:bg-slate-50">Cancel</button>
      <button id="cm-yes" type="button" class="px-4 py-2 rounded-lg text-sm text-white bg-indigo-600 hover:bg-indigo-700 font-medium">Confirm</button>
    </div>
  </div>
</div>

@if(session('celebrate'))
<div id="cel" class="fixed inset-0 z-[110] flex items-center justify-center p-4">
  <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm fade-in" onclick="document.getElementById('cel').remove()"></div>
  <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm p-7 text-center pop-in">
    <svg viewBox="0 0 52 52" class="w-20 h-20 mx-auto"><circle class="chk-c" cx="26" cy="26" r="24" fill="none" stroke="#16a34a" stroke-width="3"/><path class="chk-p" fill="none" stroke="#16a34a" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" d="M14 27l8 8 16-17"/></svg>
    <h3 class="text-xl font-bold mt-3">Investment confirmed!</h3>
    <p class="text-sm text-slate-600 mt-1">{{ session('ok') }} Your agreement slip is ready to download from this page.</p>
    <button type="button" onclick="document.getElementById('cel').remove()" class="mt-5 w-full bg-indigo-600 text-white rounded-lg py-2.5 font-medium btn-shine">Great, view my investment</button>
  </div>
</div>
<script>
(function(){var c=['#6366f1','#22c55e','#f59e0b','#ec4899','#06b6d4'];
for(var i=0;i<60;i++){var s=document.createElement('span');s.className='confetti';s.style.left=Math.random()*100+'vw';
s.style.background=c[i%c.length];s.style.animationDelay=(Math.random()*.8)+'s';s.style.animationDuration=(2+Math.random()*2)+'s';
document.body.appendChild(s);(function(x){setTimeout(function(){x.remove()},5200)})(s);}})();
</script>
@endif

<script>
(function(){
  /* count-up numbers: <span class="countup" data-v="1234.5" data-prefix="৳"> */
  document.querySelectorAll('.countup').forEach(function(el){
    var end=parseFloat(el.dataset.v||0),p=el.dataset.prefix||'',neg=end<0,t0=null,dur=800;
    function fmt(n){return (neg?'−':'')+p+Math.abs(n).toLocaleString('en-IN',{minimumFractionDigits:2,maximumFractionDigits:2});}
    function step(ts){if(!t0)t0=ts;var k=Math.min((ts-t0)/dur,1),e=1-Math.pow(1-k,3);el.textContent=fmt(end*e);if(k<1)requestAnimationFrame(step);}
    el.textContent=fmt(0);requestAnimationFrame(step);
  });
  /* deadline countdown */
  document.querySelectorAll('[data-deadline]').forEach(function(el){
    function tick(){var ms=new Date(el.dataset.deadline)-new Date();
      if(ms<=0){el.textContent='Closed';return;}
      var d=Math.floor(ms/864e5),h=Math.floor(ms%864e5/36e5),m=Math.floor(ms%36e5/6e4);
      el.textContent=(d?d+'d ':'')+h+'h '+m+'m left';}
    tick();setInterval(tick,30000);});

  /* confirmation popup */
  var m=document.getElementById('cm'),yes=document.getElementById('cm-yes'),no=document.getElementById('cm-no'),pend=null;
  function openC(f){
    pend=f;var msg=f.dataset.confirm,danger=/cancel|reject|delete|pause/i.test(msg);
    document.getElementById('cm-title').textContent=f.dataset.confirmTitle||'Please confirm';
    document.getElementById('cm-msg').textContent=msg;
    var ic=document.getElementById('cm-icon');ic.textContent=danger?'⚠️':'💳';ic.className='w-12 h-12 rounded-full flex items-center justify-center text-2xl mb-3 '+(danger?'bg-red-100':'bg-indigo-100');
    yes.textContent=f.dataset.confirmBtn||'Confirm';yes.disabled=false;
    yes.className='px-4 py-2 rounded-lg text-sm text-white font-medium '+(danger?'bg-red-600 hover:bg-red-700':'bg-indigo-600 hover:bg-indigo-700');
    m.classList.remove('hidden');m.classList.add('flex');no.focus();
  }
  function closeC(){m.classList.add('hidden');m.classList.remove('flex');pend=null;}
  function lock(f){setTimeout(function(){f.querySelectorAll('button:not([type=button])').forEach(function(b){
    if(b.dataset.locked)return;b.dataset.locked='1';b.dataset.t=b.innerHTML;b.disabled=true;b.classList.add('opacity-60');b.innerHTML='<span class="spin"></span>Please wait…';});},0);}
  document.addEventListener('submit',function(e){
    var f=e.target;
    if(f.dataset&&f.dataset.confirm&&!f.dataset.ok){e.preventDefault();openC(f);return;}
    lock(f);
  },true);
  yes.addEventListener('click',function(){var f=pend;if(!f)return;yes.disabled=true;yes.innerHTML='<span class="spin"></span>Processing…';f.dataset.ok='1';lock(f);setTimeout(function(){f.submit();},50);});
  no.addEventListener('click',closeC);document.getElementById('cm-bg').addEventListener('click',closeC);
  document.addEventListener('keydown',function(e){if(e.key==='Escape'&&pend)closeC();});
  window.addEventListener('pageshow',function(){document.querySelectorAll('button[data-locked]').forEach(function(b){b.disabled=false;b.classList.remove('opacity-60');b.innerHTML=b.dataset.t;delete b.dataset.locked;});
    document.querySelectorAll('form[data-ok]').forEach(function(f){delete f.dataset.ok;});if(m&&!m.classList.contains('hidden'))closeC();});
})();
</script>
</body></html>
