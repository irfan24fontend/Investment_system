@extends('layouts.app')
@section('content')
@php
  $canApprove = fn($s) => $s->prepared_by !== auth()->id() || auth()->user()->hasRole('super_admin');
  $fmt = fn($v) => '৳'.number_format(abs((float) $v), 2);
  $statusBn = ['prepared' => 'অনুমোদন বাকি', 'posted' => 'সম্পন্ন'];
@endphp
@include('partials.help', ['title' => 'সেটেলমেন্ট: মাত্র ২ ধাপ', 'lines' => [
  '<b>ধাপ ১ (হিসাব):</b> প্রকল্পে মোট কত টাকা এসেছে শুধু সেটা লিখে <b>Calculate</b> চাপুন। খরচ অর্ডার থেকে নিজে আসে। এতে ওয়ালেটে টাকা যায় না।',
  '<b>ধাপ ২ (অনুমোদন):</b> নিচের "অনুমোদন বাকি" তে সংখ্যা দেখে <b>Approve and post</b> চাপুন। তখন ইনভেস্টরের ওয়ালেটে টাকা জমা হয়। Super admin নিজের হিসাব নিজেই approve করতে পারে; Finance এর ক্ষেত্রে অন্য একজন করবে।',
  'ভুল হিসাব দিলে Approve এর <u>আগে</u> <b>Recalculate</b> করুন। Approve বা Offers → Progress / Complete চাপলে টাকা সঙ্গে সঙ্গে জমা হয়ে যায় এবং ফেরানো যায় না। তাই হিসাব নিয়ে নিশ্চিত না হলে এই পেজে Calculate → যাচাই → Approve করুন।'
]])

<h2 class="text-lg font-bold mb-2">১. অনুমোদন বাকি</h2>
@forelse($pending as $s)
<div class="bg-white border rounded-xl p-4 mb-3 flex flex-wrap items-center justify-between gap-3">
  <div class="text-sm space-y-0.5">
    <div class="font-semibold">{{ $s->investment->offer->code }} · {{ $s->investment->offer->title }}</div>
    <div>বিনিয়োগ {{ $fmt($s->investment->amount) }} → ইনভেস্টর ওয়ালেটে পাবে <b>{{ $fmt($s->payout) }}</b></div>
    <div class="text-xs text-slate-500">
      {{ (float) $s->net_result >= 0 ? 'প্রকল্পে লাভ '.$fmt($s->net_result) : 'প্রকল্পে ক্ষতি '.$fmt($s->net_result) }}
      @if((float) $s->investor_profit > 0) · ইনভেস্টরের লাভ +{{ $fmt($s->investor_profit) }} @endif
      @if((float) $s->investor_loss > 0) · ইনভেস্টরের ক্ষতি −{{ $fmt($s->investor_loss) }} @endif
    </div>
  </div>
  <div class="w-full text-xs bg-slate-50 border rounded-lg p-2">হিসাব: আয় {{ $fmt($s->revenue_received) }} − ফেরত {{ $fmt($s->refunds) }} − মোট খরচ {{ $fmt($s->total_costs) }} = প্রকল্পের ফল <b>{{ (float) $s->net_result < 0 ? '−' : '' }}{{ $fmt($s->net_result) }}</b></div>
  <details class="w-full text-xs"><summary class="cursor-pointer text-amber-700 font-medium">ভুল হয়েছে? আবার হিসাব করুন (Recalculate)</summary>
    <form method="POST" action="{{ route('admin.settlements.recalculate', $s) }}" class="flex flex-wrap gap-2 items-end mt-2">@csrf
      <label>প্রকল্পে মোট কত টাকা এসেছে<input name="revenue" type="number" step="0.01" min="0" required value="{{ $s->revenue_received }}" class="border rounded p-1 block"></label>
      <label>ফেরত দেওয়া টাকা<input name="refunds" type="number" step="0.01" min="0" value="{{ $s->refunds }}" class="border rounded p-1 block"></label>
      <label>অতিরিক্ত খরচ<input name="extra" type="number" step="0.01" min="0" value="{{ $s->breakdown['extraDeductions'] ?? 0 }}" class="border rounded p-1 block"></label>
      <button class="bg-amber-600 text-white px-3 py-1.5 rounded-lg">Recalculate</button>
    </form>
    <div class="text-slate-500 mt-1">খরচ বদলাতে হলে আগে Orders এ ঠিক করুন, তারপর Recalculate দিন। Approve হয়ে গেলে আর Recalculate হয় না।</div>
  </details>
  @if($canApprove($s))
    <form method="POST" action="{{ route('admin.settlements.approve', $s) }}" onsubmit="return confirm('ইনভেস্টরের ওয়ালেটে টাকা জমা হবে। ফেরানো যাবে না। নিশ্চিত?')">@csrf
      <button class="bg-green-600 text-white px-4 py-2 rounded-lg text-sm">Approve and post</button></form>
  @else
    <span class="text-xs text-slate-500">অন্য একজন Finance user approve করবে</span>
  @endif
</div>
@empty
<p class="text-slate-500 text-sm mb-4">কিছু নেই।</p>
@endforelse

<h2 class="text-lg font-bold mt-6 mb-2">২. হিসাব করা বাকি</h2>
@forelse($ready as $i)
<form method="POST" action="{{ route('admin.settlements.prepare', $i) }}" class="bg-white border rounded-xl p-4 mb-3 flex flex-wrap gap-3 items-end">@csrf
  <div class="text-sm"><b>{{ $i->offer->code }}</b> · {{ $i->offer->title }}<br><span class="text-slate-500">বিনিয়োগ {{ $fmt($i->amount) }}</span></div>
  <label class="text-xs">প্রকল্পে মোট কত টাকা এসেছে (৳)<input name="revenue" type="number" step="0.01" min="0" required class="border rounded p-2 block text-sm"></label>
  <details class="text-xs"><summary class="cursor-pointer text-slate-500">আরও অপশন (ফেরত / অতিরিক্ত খরচ)</summary>
    <div class="flex gap-2 mt-1">
      <label>ফেরত দেওয়া টাকা<input name="refunds" type="number" step="0.01" min="0" class="border rounded p-1 block"></label>
      <label>অতিরিক্ত খরচ<input name="extra" type="number" step="0.01" min="0" class="border rounded p-1 block"></label>
    </div></details>
  <button class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm">Calculate</button>
</form>
@empty
<p class="text-slate-500 text-sm mb-4">কিছু নেই। (অর্ডার financial_verification এ গেলে এখানে আসবে)</p>
@endforelse

<h2 class="text-lg font-bold mt-6 mb-2">ইতিহাস</h2>
<div class="bg-white rounded-xl border overflow-x-auto"><table class="w-full text-sm">
<tr class="bg-slate-100 text-left"><th class="p-2">Offer</th><th>বিনিয়োগ</th><th>প্রকল্পের ফল</th><th>ইনভেস্টর পেল</th><th>অবস্থা</th></tr>
@foreach($rows as $s)
<tr class="border-t">
  <td class="p-2">{{ $s->investment->offer->code }}</td>
  <td>{{ $fmt($s->investment->amount) }}</td>
  <td class="{{ (float) $s->net_result < 0 ? 'text-red-700' : 'text-green-700' }}">{{ (float) $s->net_result < 0 ? 'ক্ষতি' : 'লাভ' }} {{ $fmt($s->net_result) }}</td>
  <td><b>{{ $fmt($s->payout) }}</b>
    @if((float) $s->investor_profit > 0)<span class="text-xs text-green-700">(+{{ $fmt($s->investor_profit) }} লাভ)</span>@endif
    @if((float) $s->investor_loss > 0)<span class="text-xs text-red-700">(−{{ $fmt($s->investor_loss) }} ক্ষতি)</span>@endif</td>
  <td>{{ $statusBn[$s->status] ?? $s->status }}</td>
</tr>
@endforeach
</table></div>{{ $rows->links() }}
@endsection
