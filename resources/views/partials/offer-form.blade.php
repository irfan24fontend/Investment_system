@php
  $o = $offer ?? null;
  $fields = [
    ['code','Offer ID','text',$o?->code,'ইউনিক আইডি, যেমন OFR-001'],
    ['title','Title','text',$o?->title,'ইনভেস্টর যে নামে অফারটি দেখবে'],
    ['product_name','Product','text',$o?->product_name,'যেমন 500ml PET বোতল'],
    ['quantity','Quantity','number',$o?->quantity,'মোট কত পিস'],
    ['unit_price','Unit price','number',$o?->unit_price,'প্রতি পিসের ক্রয়মূল্য (৳)'],
    ['other_expenses','Transport/other','number',$o?->other_expenses,'পরিবহন ও অন্যান্য খরচ (৳)'],
    ['business_contribution','Business contribution','number',$o?->business_contribution,'আপনি নিজে কত দিচ্ছেন (৳)। 0 হলে ইনভেস্টরই পুরো টাকা দেবে'],
    ['investor_return_pct','Investor return % (বিনিয়োগের উপর)','number',$o?->settlement_rules['investor_return_pct'] ?? null,'ইনভেস্টরের বিনিয়োগের উপর কত % লাভ, যেমন 10। এটি target, গ্যারান্টি নয়'],
    ['deadline_at','Deadline','datetime-local',$o?->deadline_at?->format('Y-m-d\TH:i'),'এর পর কেউ invest করতে পারবে না (ঢাকার সময়)'],
    ['estimated_settlement_date','Est. settlement','date',$o?->estimated_settlement_date ? \Illuminate\Support\Carbon::parse($o->estimated_settlement_date)->format('Y-m-d') : null,'আনুমানিক কবে হিসাব শেষ হবে (ঐচ্ছিক)'],
  ];
  $calc = ['quantity','unit_price','other_expenses','business_contribution','investor_return_pct'];
  $init = [];
  foreach ($calc as $c) { $init[$c] = old($c, $o?->{$c}); }
  $init['investor_return_pct'] = old('investor_return_pct', $o?->settlement_rules['investor_return_pct'] ?? null);
@endphp
<form method="POST" action="{{ $action }}" x-data="{{ json_encode($init) }}" class="bg-white p-4 rounded shadow grid md:grid-cols-3 gap-3 mb-6">@csrf
@foreach($fields as [$n,$l,$t,$def,$hint])
<label class="text-xs font-medium">{{ $l }}<input name="{{ $n }}" type="{{ $t }}" @if($t==='number') step="0.01" @endif @if(in_array($n,$calc)) x-model="{{ $n }}" @endif value="{{ old($n, $def) }}" class="w-full border rounded p-2 text-sm font-normal"><span class="block text-[11px] font-normal text-slate-500 mt-0.5">{{ $hint }}</span></label>
@endforeach
<label class="text-xs font-medium">Category<select name="category" class="w-full border rounded p-2 text-sm font-normal">
@foreach(['order_financing'=>'Order financing (কাস্টমারের অর্ডার)','stock_financing'=>'Stock financing (স্টক কেনা)','bulk_purchase_financing'=>'Bulk purchase (পাইকারি ক্রয়)'] as $v=>$lbl)<option value="{{ $v }}" @selected(old('category',$o?->category)===$v)>{{ $lbl }}</option>@endforeach</select></label>
<div class="md:col-span-3 bg-indigo-50 border border-indigo-200 rounded p-3 text-sm text-indigo-900">
  <b>হিসাব (সিস্টেম সেভ করার সময় নিজে আবার হিসাব করে):</b><br>
  Total cost = ৳<b x-text="(Number(quantity||0)*Number(unit_price||0)+Number(other_expenses||0)).toFixed(2)"></b>
  &nbsp;|&nbsp; ইনভেস্টরকে দিতে হবে = ৳<b x-text="Math.max(0, Number(quantity||0)*Number(unit_price||0)+Number(other_expenses||0)-Number(business_contribution||0)).toFixed(2)"></b>
  &nbsp;|&nbsp; ইনভেস্টরের target profit = ৳<b x-text="(Math.max(0, Number(quantity||0)*Number(unit_price||0)+Number(other_expenses||0)-Number(business_contribution||0))*Number(investor_return_pct||0)/100).toFixed(2)"></b>
</div>
<label class="text-xs font-medium md:col-span-3">Description<textarea name="description" rows="3" class="w-full border rounded p-2 text-sm font-normal">{{ old('description', $o?->description ?? \App\Models\InvestmentOffer::DEFAULT_DESCRIPTION) }}</textarea><span class="block text-[11px] font-normal text-slate-500">ইনভেস্টর এটি পড়ে বুঝবে টাকা কোথায় যাবে।</span></label>
<label class="text-xs font-medium md:col-span-3">Risk disclosure<textarea name="risk_disclosure" rows="3" required class="w-full border rounded p-2 text-sm font-normal">{{ old('risk_disclosure', $o?->risk_disclosure ?? \App\Models\InvestmentOffer::DEFAULT_RISK) }}</textarea><span class="block text-[11px] font-normal text-slate-500">ঝুঁকির কথা অবশ্যই থাকবে। লাভের নিশ্চয়তা লিখবেন না।</span></label>
<button class="bg-indigo-600 text-white py-2 rounded">{{ $button ?? 'Save' }}</button>
</form>
