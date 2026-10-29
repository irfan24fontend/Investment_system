@extends('layouts.app')
@section('content')
@php $role = auth()->user()->role; $staff = $role !== 'investor'; @endphp
<h1 class="text-2xl font-bold mb-1">Guide: সিস্টেম কীভাবে কাজ করে</h1>
<p class="text-sm text-slate-500 mb-5">আপনার রোল: <b>{{ str_replace('_',' ',$role) }}</b></p>

<div class="bg-white border rounded-xl p-5 mb-5">
  <h2 class="font-semibold mb-3">পুরো workflow এক নজরে</h2>
  <ol class="space-y-2 text-sm list-decimal pl-5">
    <li><b>ইনভেস্টর register করে</b> → Finance/Super admin তাকে <b>Verify</b> করে।</li>
    <li><b>ইনভেস্টর ডিপোজিট জমা দেয়</b> (Islami Bank slip বা bKash TrxID) → Finance টাকা এসেছে দেখে <b>Approve</b> করে → ওয়ালেটে টাকা যোগ হয়।</li>
    <li><b>Super admin অফার বানায়</b> (draft) → <b>Publish</b> করে → verified ইনভেস্টররা দেখে।</li>
    <li><b>একজন ইনভেস্টর Invest চাপে</b> → ওয়ালেট থেকে টাকা কাটে, অফার <b>funded</b> হয়, অন্য সবার কাছ থেকে অফার সরে যায়।</li>
    <li><b>কাজ চলে:</b> Order Manager অর্ডারের status দেয়, Super admin Progress update দেয়। ইনভেস্টর My Investments এ দেখে।</li>
    <li><b>কাজ শেষ:</b> আসল revenue ও খরচ দিয়ে হিসাব হয় (Super admin <i>Complete</i>, অথবা Finance <i>Calculate</i> → অন্য Finance <i>Approve</i>)।</li>
    <li><b>ইনভেস্টরের ওয়ালেটে</b> মূলধন + লাভ (বা − লোকসান) জমা হয়। চাইলে <b>Withdraw</b> দেয়, স্টাফ টাকা পাঠালে সম্পন্ন।</li>
  </ol>
</div>

<div class="grid md:grid-cols-2 gap-5 mb-5">
@if($role==='investor')
  <div class="bg-white border rounded-xl p-5 text-sm space-y-2 md:col-span-2"><h2 class="font-semibold">আপনার করণীয়</h2>
    <p>Wallet → ডিপোজিট জমা দিন → স্টাফ approve করলে ব্যালেন্স বাড়বে → Offers থেকে বেছে Invest → My Investments এ অগ্রগতি দেখুন → শেষে টাকা Wallet এ আসবে → Withdraw।</p>
    <p class="text-amber-800">মনে রাখবেন: লাভের নিশ্চয়তা নেই। কাজে লোকসান হলে আপনি বিনিয়োগের চেয়ে কম ফেরত পেতে পারেন।</p></div>
@endif
@if($role==='super_admin')
  <div class="bg-white border rounded-xl p-5 text-sm space-y-2"><h2 class="font-semibold">Super admin: কোন মেনুতে কী</h2>
    <p><b>Offers:</b> অফার বানান, Publish/Pause/Cancel, funded হলে Progress ও Complete।</p>
    <p><b>Investors:</b> Verify। <b>Deposits/Withdrawals:</b> টাকা অনুমোদন। <b>Settlements:</b> হিসাব অনুমোদন।</p>
    <p><b>Orders:</b> অর্ডার ও খরচ। <b>Reports:</b> হিসাব মিলছে কিনা। <b>Audit:</b> কে কী করেছে।</p></div>
@endif
@if($role==='finance_manager')
  <div class="bg-white border rounded-xl p-5 text-sm space-y-2"><h2 class="font-semibold">Finance: কোন মেনুতে কী</h2>
    <p><b>Investors:</b> যাচাই। <b>Deposits:</b> টাকা এসেছে দেখে Approve। <b>Withdrawals:</b> টাকা পাঠিয়ে Mark paid।</p>
    <p><b>Settlements:</b> Calculate করুন; অন্য Finance user Approve করবে। <b>Reports:</b> ledger মিলছে কিনা।</p></div>
@endif
@if($role==='order_manager')
  <div class="bg-white border rounded-xl p-5 text-sm space-y-2"><h2 class="font-semibold">Order Manager: করণীয়</h2>
    <p>Orders এ অর্ডার ও খরচ লিখুন, offer এর সাথে link করুন। ইনভেস্টর টাকা দিলে procurement → ready → delivered করুন। শেষে <b>financial_verification</b> দিন।</p>
    <p>আপনি ইনভেস্টরের টাকা, deposit বা settlement ছুঁতে পারবেন না।</p></div>
@endif
  <div class="bg-white border rounded-xl p-5 text-sm space-y-2"><h2 class="font-semibold">লাভ-লোকসান কীভাবে হিসাব হয়</h2>
    <p>Net = revenue − refund − মোট খরচ।</p>
    <p>Net লাভ হলে ইনভেস্টর পায়: (বিনিয়োগ × return%) অথবা প্রকৃত Net লাভ, যেটি <b>কম</b>। যেমন ৳10,000 এ 10% = ৳1,000 target।</p>
    <p>Net লোকসান হলে ইনভেস্টর বহন করে: লোকসান × (তার বিনিয়োগ ÷ মোট প্রকল্প খরচ), তার বিনিয়োগের বেশি নয়।</p>
    <p>ওয়ালেটে আসে: বিনিয়োগ + profit − loss। শুধু আসল প্রাপ্ত টাকা ধরা হয়, আনুমানিক নয়।</p></div>
</div>

<div class="bg-white border rounded-xl p-5 mb-5"><h2 class="font-semibold mb-3">স্ট্যাটাসের মানে</h2>
  <div class="grid md:grid-cols-2 gap-x-8 gap-y-1 text-sm">
    <div><b>Offer:</b></div><div></div>
    <div>draft</div><div class="text-slate-600">বানানো হয়েছে, ইনভেস্টর দেখে না</div>
    <div>published</div><div class="text-slate-600">ইনভেস্টরদের কাছে খোলা</div>
    <div>paused</div><div class="text-slate-600">সাময়িক লুকানো</div>
    <div>funded</div><div class="text-slate-600">একজন ইনভেস্টর টাকা দিয়েছে, কাজ চলছে</div>
    <div>completed</div><div class="text-slate-600">হিসাব শেষ, টাকা ওয়ালেটে গেছে</div>
    <div>expired</div><div class="text-slate-600">deadline পার হয়েছে, কেউ invest করেনি</div>
    <div>cancelled</div><div class="text-slate-600">বাতিল</div>
    <div class="mt-2"><b>Deposit / Withdrawal:</b></div><div></div>
    <div>pending</div><div class="text-slate-600">স্টাফের যাচাইয়ের অপেক্ষায়</div>
    <div>confirmed / completed</div><div class="text-slate-600">সম্পন্ন</div>
    <div>rejected</div><div class="text-slate-600">বাতিল (কারণ লেখা থাকে); withdraw হলে টাকা ওয়ালেটে ফেরত</div>
    <div class="mt-2"><b>Investment:</b></div><div></div>
    <div>Active</div><div class="text-slate-600">কাজ চলছে</div>
    <div>Settled / Settled (loss)</div><div class="text-slate-600">শেষ; লাভসহ / লোকসানসহ ফেরত</div>
  </div>
</div>

<div class="bg-white border rounded-xl p-5"><h2 class="font-semibold mb-3">সচরাচর প্রশ্ন</h2>
  <div class="space-y-3 text-sm">
    <div><b>দুজন একসাথে invest চাপলে?</b><br>একজনই পাবে। অন্যজন "Offer already funded" দেখবে এবং তার টাকা কাটা হবে না।</div>
    <div><b>Deadline পার হলে?</b><br>কেউ আর invest করতে পারে না; অফার expired হয়।</div>
    <div><b>ডিপোজিট দিলাম কিন্তু ব্যালেন্সে নেই কেন?</b><br>স্টাফ টাকা পেয়েছে নিশ্চিত করে Approve না করা পর্যন্ত ব্যালেন্স বাড়ে না।</div>
    <div><b>Funded অফার বদলানো বা বাতিল করা যায়?</b><br>না। ইনভেস্টরের টাকা জড়িত থাকায় শুধু Complete করা যায়।</div>
    <div><b>ভুল approve করে ফেললে?</b><br>লেনদেন মুছা যায় না। Super admin কে জানান, reversal entry দিয়ে ঠিক করতে হয়।</div>
  </div>
</div>
@endsection
