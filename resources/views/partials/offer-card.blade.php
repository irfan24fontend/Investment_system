<a href="{{ route('offers.show', $o) }}" class="group bg-white rounded-xl border shadow-sm hover:shadow-md transition p-4 flex flex-col gap-2">
  <div class="flex items-center justify-between text-xs">
    <span class="px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700">{{ ucwords(str_replace('_',' ', $o->category)) }}</span>
    <span class="text-slate-400">#{{ $o->code }}</span>
  </div>
  <h3 class="font-semibold text-slate-900 group-hover:text-indigo-700">{{ $o->title }}</h3>
  <div class="text-sm text-slate-600">{{ $o->product_name }} × {{ number_format($o->quantity) }}</div>
  <div class="mt-1">
    <div class="text-xs text-slate-500">Required investment</div>
    <div class="text-2xl font-bold text-slate-900">৳{{ number_format($o->required_amount, 2) }}</div>
  </div>
  @if($o->targetProfit() !== null)<div class="inline-block self-start bg-green-50 text-green-800 border border-green-200 rounded-lg px-2 py-1 text-sm font-semibold">Target profit ৳{{ number_format($o->targetProfit(),2) }} <span class="font-normal text-xs">({{ rtrim(rtrim($o->returnPct(),'0'),'.') }}%)</span></div>@endif
  <div class="grid grid-cols-2 gap-2 text-xs text-slate-500 border-t pt-2 mt-1">
    <div>Closes in<div class="font-semibold text-amber-700" data-deadline="{{ $o->deadline_at->toIso8601String() }}">…</div></div>
    <div>Est. settlement<div class="font-semibold text-slate-700">{{ $o->estimated_settlement_date ? $o->estimated_settlement_date->format('d M Y') : 'TBD' }}</div></div>
  </div>
  <div class="text-[11px] text-slate-400">Target profit গ্যারান্টি নয়; প্রকৃত ফলাফলের উপর নির্ভর করে। লোকসান সম্ভব।</div>
  <span class="mt-1 text-center bg-indigo-600 group-hover:bg-indigo-700 text-white rounded-lg py-2 text-sm font-medium">View details</span>
</a>
