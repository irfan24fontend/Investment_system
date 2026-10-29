@php
  $themes = [
    'order_financing'         => ['from-indigo-500','to-violet-600','📦','Order financing'],
    'stock_financing'         => ['from-emerald-500','to-teal-600','🏭','Stock financing'],
    'bulk_purchase_financing' => ['from-amber-500','to-orange-600','🧴','Bulk purchase'],
  ];
  $th = $themes[$o->category] ?? ['from-slate-500','to-slate-700','💼','Offer'];
  $secs = $o->deadline_at->timestamp - time();
  $urgent = $secs > 0 && $secs < 86400;
  $share = $o->settlement_rules['investor_profit_share_pct'] ?? 0;
@endphp
<a href="{{ route('offers.show', $o) }}" class="group relative block rounded-2xl bg-white border border-slate-200 shadow-sm overflow-hidden transition duration-300 hover:-translate-y-1.5 hover:shadow-2xl hover:border-indigo-200 fade-up" style="animation-delay: {{ ($idx ?? 0) * 90 }}ms">
  <div class="relative h-28 bg-gradient-to-br {{ $th[0] }} {{ $th[1] }} p-4 text-white overflow-hidden">
    <div class="absolute -right-8 -top-8 w-32 h-32 rounded-full bg-white/10 transition duration-500 group-hover:scale-125"></div>
    <div class="absolute -left-6 -bottom-10 w-28 h-28 rounded-full bg-white/10"></div>
    <div class="relative flex items-start justify-between">
      <span class="text-xs bg-white/20 backdrop-blur rounded-full px-2.5 py-0.5">{{ $th[3] }}</span>
      @if($urgent)<span class="text-[11px] bg-red-500 rounded-full px-2 py-0.5 pulse-ring">⏰ Closing soon</span>@endif
    </div>
    <div class="absolute right-4 bottom-2 text-5xl float-slow drop-shadow">{{ $th[2] }}</div>
    <div class="absolute left-4 bottom-3 text-xs opacity-90">#{{ $o->code }}</div>
  </div>

  <div class="p-4">
    <h3 class="font-bold text-slate-900 leading-snug line-clamp-2 min-h-[2.75rem] group-hover:text-indigo-700 transition">{{ $o->title }}</h3>
    <div class="text-sm text-slate-500 mt-0.5">{{ $o->product_name }} × {{ number_format($o->quantity) }}</div>

    <div class="mt-3 rounded-xl bg-slate-50 border border-slate-100 p-3">
      <div class="text-[11px] uppercase tracking-wide text-slate-500">Required investment</div>
      <div class="text-2xl font-extrabold text-slate-900">৳{{ number_format($o->required_amount, 2) }}</div>
    </div>

    <div class="grid grid-cols-2 gap-2 mt-3 text-xs">
      <div class="rounded-lg bg-amber-50 text-amber-800 p-2"><div class="opacity-70">Closes in</div><div class="font-bold" data-deadline="{{ $o->deadline_at->toIso8601String() }}">…</div></div>
      <div class="rounded-lg bg-sky-50 text-sky-800 p-2"><div class="opacity-70">Est. settlement</div><div class="font-bold">{{ $o->estimated_settlement_date ? $o->estimated_settlement_date->format('d M Y') : 'TBD' }}</div></div>
    </div>

    <div class="mt-3 flex flex-wrap gap-1.5 text-[11px]">
      <span class="px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700">Your share: {{ $share }}% of net profit</span>
      <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">🔒 One investor only</span>
    </div>
    <div class="mt-2 text-[11px] text-slate-400">Not guaranteed. Loss is possible.</div>

    <span class="btn-shine mt-3 block text-center bg-gradient-to-r from-indigo-600 to-violet-600 group-hover:from-indigo-700 group-hover:to-violet-700 text-white rounded-xl py-2.5 text-sm font-semibold shadow">View &amp; invest →</span>
  </div>
</a>
