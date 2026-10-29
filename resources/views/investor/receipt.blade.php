@php
  $snap = $i->agreement_snapshot;
  $no = 'INV-'.str_pad($i->id, 6, '0', STR_PAD_LEFT);
  $dt = fn($v, $f = 'd M Y, h:i A') => $v ? \Illuminate\Support\Carbon::parse($v)->timezone('Asia/Dhaka')->format($f) : 'To be announced';
  $pct = $snap['rules']['investor_profit_share_pct'] ?? 0;
  $rp = $snap['rules']['investor_return_pct'] ?? null;
  $co = config('services.company');
  $dash = fn($v) => filled($v) ? $v : '—';
  $code = implode('-', str_split(strtoupper(substr(hash('sha256', $i->id.'|'.json_encode($snap)), 0, 12)), 4));
  $amt = number_format((float) $i->amount, 2);
  $status = $i->status === 'confirmed' ? 'Active (চলমান)' : ucfirst(str_replace('_', ' ', $i->status));
@endphp
<!doctype html>
<html lang="bn"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Investment Agreement {{ $no }}</title>
<link href="https://fonts.googleapis.com/css2?family=Noto+Serif+Bengali:wght@400;600;700&family=Hind+Siliguri:wght@400;500;600&display=swap" rel="stylesheet">
<style>
  *{box-sizing:border-box}
  body{margin:0;background:#e5e7eb;color:#111827;font-family:'Noto Serif Bengali','Hind Siliguri','Times New Roman',serif;font-size:14px;line-height:1.7}
  .bar{max-width:820px;margin:14px auto;display:flex;gap:8px;justify-content:flex-end;padding:0 8px}
  .bar a,.bar button{padding:8px 14px;border-radius:6px;border:1px solid #9ca3af;background:#fff;font:inherit;font-size:13px;cursor:pointer;text-decoration:none;color:#111827}
  .bar .p{background:#1e3a8a;color:#fff;border-color:#1e3a8a}
  .sheet{max-width:820px;margin:0 auto 24px;background:#fff;padding:14px}
  .frame{border:3px double #1e293b;padding:26px 32px}
  .head{text-align:center;border-bottom:2px solid #1e293b;padding-bottom:12px}
  .head .org{font-size:20px;font-weight:700;letter-spacing:.3px}
  .head .sub{font-size:12px;color:#374151}
  .title{text-align:center;margin:16px 0 4px;font-size:20px;font-weight:700;text-decoration:underline;text-underline-offset:5px}
  .title-en{text-align:center;font-size:12px;letter-spacing:2px;color:#374151;margin-bottom:12px}
  .meta{display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;font-size:12.5px;border:1px solid #9ca3af;padding:8px 12px;margin-bottom:14px}
  h2{font-size:14.5px;margin:18px 0 6px;text-transform:none;border-bottom:1px solid #6b7280;padding-bottom:2px}
  p{margin:6px 0;text-align:justify}
  table{width:100%;border-collapse:collapse;font-size:13px;margin:6px 0}
  th,td{border:1px solid #6b7280;padding:5px 8px;text-align:left;vertical-align:top}
  th{background:#f3f4f6;width:34%;font-weight:600}
  .amount{font-size:16px;font-weight:700}
  .cl{margin:10px 0;page-break-inside:avoid}
  .cl b.h{display:block;margin-bottom:1px}
  .en{font-size:11.5px;color:#4b5563;font-family:Arial,Helvetica,sans-serif;line-height:1.5}
  .risk{border:1px solid #6b7280;background:#f9fafb;padding:8px 10px;white-space:pre-line;font-size:13px}
  .sign{display:flex;gap:28px;margin-top:26px;page-break-inside:avoid}
  .sign>div{flex:1;font-size:12.5px}
  .line{border-bottom:1px solid #111827;height:44px;margin-bottom:4px}
  .esign{border:1px dashed #1e3a8a;padding:8px;min-height:44px;font-size:12.5px;color:#1e3a8a;margin-bottom:4px}
  .foot{margin-top:18px;border-top:1px solid #9ca3af;padding-top:8px;font-size:11px;color:#4b5563;display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap}
  .stamp{display:inline-block;border:2px solid #166534;color:#166534;padding:1px 10px;font-weight:700;font-size:12px;letter-spacing:1px}
  @page{size:A4;margin:12mm}
  @media print{body{background:#fff}.bar{display:none}.sheet{padding:0;margin:0;max-width:none}}
  @media(max-width:600px){.frame{padding:16px 14px}.sign{flex-direction:column}}
</style></head><body>
<div class="bar"><a href="{{ route('investments.show',$i) }}">← Back</a><a href="{{ route('investments.receipt',[$i,'download'=>1]) }}">Download</a><button class="p" onclick="window.print()">Print / Save as PDF</button></div>
<div class="sheet"><div class="frame">

  <div class="head">
    <div class="org">{{ $co['legal_name'] ?? config('app.name') }}</div>
    <div class="sub">{{ $dash($co['address'] ?? null) }}</div>
    <div class="sub">ট্রেড লাইসেন্স নং: {{ $dash($co['trade_license'] ?? null) }} &nbsp;|&nbsp; ফোন: {{ $dash($co['phone'] ?? null) }} &nbsp;|&nbsp; ইমেইল: {{ $dash($co['email'] ?? null) }}</div>
  </div>

  <div class="title">বিনিয়োগ চুক্তিপত্র</div>
  <div class="title-en">INVESTMENT AGREEMENT</div>

  <div class="meta">
    <div>চুক্তি নং: <b>{{ $no }}</b></div>
    <div>অফার নং: <b>{{ $snap['offer_code'] ?? $i->offer->code }}</b></div>
    <div>তারিখ: <b>{{ $dt($snap['accepted_at'], 'd M Y') }}</b></div>
    <div><span class="stamp">CONFIRMED</span></div>
  </div>

  <p>এই চুক্তিপত্র <b>{{ $dt($snap['accepted_at'], 'd M Y') }}</b> তারিখে ইলেকট্রনিক মাধ্যমে নিম্নলিখিত পক্ষগণের মধ্যে সম্পাদিত হলো:</p>

  <h2>পক্ষসমূহ (Parties)</h2>
  <table>
    <tr><th>প্রথম পক্ষ (কোম্পানি)</th><td><b>{{ $co['legal_name'] ?? config('app.name') }}</b>, ঠিকানা: {{ $dash($co['address'] ?? null) }}</td></tr>
    <tr><th>দ্বিতীয় পক্ষ (বিনিয়োগকারী)</th><td><b>{{ $i->user->name }}</b><br>মোবাইল: {{ $dash($i->user->phone) }} &nbsp;|&nbsp; ইমেইল: {{ $i->user->email }}</td></tr>
  </table>

  <p>যেহেতু কোম্পানি নিম্নোক্ত তফসিলে বর্ণিত ব্যবসায়িক কার্যক্রমের অর্থায়নের জন্য একটি বিনিয়োগ প্রস্তাব প্রকাশ করেছে এবং বিনিয়োগকারী উক্ত প্রস্তাবে অংশগ্রহণে সম্মত হয়েছেন, সেহেতু উভয় পক্ষ নিম্নলিখিত শর্তাবলিতে সম্মত হলেন।</p>

  <h2>তফসিল (Schedule)</h2>
  <table>
    <tr><th>প্রস্তাবের শিরোনাম</th><td>{{ $snap['title'] ?? $i->offer->title }}</td></tr>
    <tr><th>পণ্য ও পরিমাণ</th><td>{{ $snap['product'] ?? $i->offer->product_name }} × {{ number_format($snap['quantity'] ?? $i->offer->quantity) }}</td></tr>
    <tr><th>প্রকল্পের মোট ব্যয়</th><td>৳{{ number_format((float) ($snap['total_cost'] ?? $i->offer->total_cost), 2) }}</td></tr>
    <tr><th>বিনিয়োগকৃত অর্থ</th><td class="amount">৳{{ $amt }}</td></tr>
    <tr><th>অর্থ প্রদানের মাধ্যম</th><td>প্ল্যাটফর্ম ওয়ালেট ব্যালেন্স</td></tr>
    <tr><th>সম্মতির সময়</th><td>{{ $dt($snap['accepted_at']) }} (Asia/Dhaka)</td></tr>
    <tr><th>লেনদেন রেফারেন্স</th><td style="word-break:break-all">{{ $tx->uuid ?? 'N/A' }}</td></tr>
    <tr><th>আনুমানিক সেটেলমেন্ট</th><td>{{ $dt($snap['estimated_settlement_date'] ?? $i->offer->estimated_settlement_date, 'd M Y') }}</td></tr>
    <tr><th>বর্তমান অবস্থা</th><td>{{ $status }}</td></tr>
  </table>

  <h2>শর্তাবলি (Terms and Conditions)</h2>

  <div class="cl"><b class="h">ধারা ১: বিনিয়োগের বিষয় ও পরিমাণ</b>
    <p>বিনিয়োগকারী তফসিলে উল্লিখিত প্রকল্পে মোট <b>৳{{ $amt }}</b> বিনিয়োগ করেছেন। অর্থ তাঁর ওয়ালেট থেকে কর্তন করে এই প্রকল্পের জন্য আবদ্ধ রাখা হয়েছে।</p>
    <div class="en">1. The Investor has invested BDT {{ $amt }} in the project in the Schedule. The amount was debited from the wallet and held for this project.</div></div>

  <div class="cl"><b class="h">ধারা ২: একক বিনিয়োগকারী</b>
    <p>এই প্রস্তাবে কেবল একজন বিনিয়োগকারী অংশ নিতে পারেন। বিনিয়োগ নিশ্চিত হওয়ার সঙ্গে সঙ্গে প্রস্তাবটি অন্য সকল বিনিয়োগকারীর জন্য বন্ধ হয়ে গেছে।</p>
    <div class="en">2. Only one investor may fund an offer. It closed to all others as soon as this investment was confirmed.</div></div>

  <div class="cl"><b class="h">ধারা ৩: প্রত্যাশিত রিটার্ন</b>
    @if($rp !== null)
    <p>বিনিয়োগকৃত অর্থের <b>{{ rtrim(rtrim((string) $rp, '0'), '.') }}%</b> (অর্থাৎ ৳{{ number_format((float) ($snap['target_profit'] ?? 0), 2) }}) হলো <b>লক্ষ্যমাত্রা (target)</b>। এই অর্থ শুধুমাত্র প্রকল্পের <b>প্রকৃত লাভ</b> থেকে প্রদান করা হবে এবং কোনোক্রমেই প্রকৃত লাভের অধিক হবে না। প্রকৃত লাভ লক্ষ্যমাত্রার কম হলে বিনিয়োগকারী প্রকৃত লাভের সমপরিমাণ পাবেন। <b>কোনো লাভ বা রিটার্নের নিশ্চয়তা নেই।</b></p>
    <div class="en">3. {{ rtrim(rtrim((string) $rp, '0'), '.') }}% of the invested amount (BDT {{ number_format((float) ($snap['target_profit'] ?? 0), 2) }}) is a target only, payable solely from actual project profit and never exceeding it. No profit or return is guaranteed.</div>
    @else
    <p>বিনিয়োগকারী প্রকল্পের <b>প্রকৃত নিট লাভের {{ $pct }}%</b> পাবেন। <b>কোনো লাভ বা রিটার্নের নিশ্চয়তা নেই।</b></p>
    <div class="en">3. The Investor receives {{ $pct }}% of the actual net profit. No profit or return is guaranteed.</div>
    @endif</div>

  <div class="cl"><b class="h">ধারা ৪: নিট ফলাফল নির্ধারণ</b>
    <p>নিট ফলাফল = প্রকৃতপক্ষে প্রাপ্ত আয় − ফেরত প্রদত্ত অর্থ − প্রকল্পের প্রকৃত খরচ। প্রাপ্ত আয়কে লাভ হিসেবে গণ্য করা হবে না।</p>
    <div class="en">4. Net result = actual revenue received − refunds − actual project costs. Revenue is not treated as profit.</div></div>

  <div class="cl"><b class="h">ধারা ৫: লোকসান</b>
    <p>প্রকল্পে লোকসান হলে বিনিয়োগকারী তাঁর বিনিয়োগের অনুপাতে লোকসান বহন করবেন, যা সর্বোচ্চ বিনিয়োগকৃত অর্থের পরিমাণ পর্যন্ত হতে পারে। ফলে বিনিয়োগকারী তাঁর বিনিয়োগের চেয়ে কম অর্থ ফেরত পেতে পারেন।</p>
    <div class="en">5. In case of loss the Investor bears it in proportion to the amount funded, up to the invested amount. The Investor may receive less than the amount invested.</div></div>

  <div class="cl"><b class="h">ধারা ৬: সেটেলমেন্ট</b>
    <p>ডেলিভারি, গ্রাহকের পেমেন্ট ও খরচ যাচাই এবং কোম্পানির অনুমোদনের পরেই সেটেলমেন্ট সম্পন্ন হবে। নির্দিষ্ট সংখ্যক দিন অতিবাহিত হলেই স্বয়ংক্রিয়ভাবে অর্থ ছাড় করা হবে না। অনুমোদিত অর্থ বিনিয়োগকারীর ওয়ালেটে জমা করা হবে।</p>
    <div class="en">6. Settlement occurs only after delivery, customer payment and costs are verified and approved. Funds are not released automatically after a number of days. The approved amount is credited to the Investor's wallet.</div></div>

  <div class="cl"><b class="h">ধারা ৭: অর্থ উত্তোলনে বিধিনিষেধ</b>
    <p>সেটেলমেন্ট সম্পন্ন না হওয়া পর্যন্ত বিনিয়োগকৃত অর্থ উত্তোলন করা যাবে না।</p>
    <div class="en">7. The invested amount cannot be withdrawn before settlement.</div></div>

  <div class="cl"><b class="h">ধারা ৮: ঝুঁকি সম্পর্কিত স্বীকৃতি</b>
    <p>বিনিয়োগকারী নিম্নোক্ত ঝুঁকি সংক্রান্ত তথ্য পাঠ করে বুঝে সম্মতি প্রদান করেছেন।</p>
    <div class="risk">{{ $snap['risk'] }}</div>
    <div class="en">8. The Investor has read and understood the risk disclosure above before agreeing.</div></div>

  <div class="cl"><b class="h">ধারা ৯: ইলেকট্রনিক সম্পাদন ও রেকর্ড</b>
    <p>এই চুক্তি ইলেকট্রনিক মাধ্যমে সম্পাদিত। বিনিয়োগকারীর অনলাইন সম্মতির সময় ও লেনদেন রেফারেন্স সিস্টেমে সংরক্ষিত আছে। এই চুক্তির শর্তাবলি সম্মতির মুহূর্তে সংরক্ষিত তথ্যের ভিত্তিতে প্রস্তুত।</p>
    <div class="en">9. This agreement is executed electronically. The time of the Investor's online acceptance and the transaction reference are recorded by the system.</div></div>

  <div class="cl"><b class="h">ধারা ১০: অভিযোগ ও প্রযোজ্য আইন</b>
    <p>এই চুক্তি সংক্রান্ত কোনো প্রশ্ন বা অভিযোগ প্ল্যাটফর্মের সহায়তা বিভাগের মাধ্যমে জানাতে হবে। এই চুক্তি বাংলাদেশের প্রচলিত আইন অনুযায়ী পরিচালিত হবে।</p>
    <div class="en">10. Queries or complaints must be raised through the platform's support channel. This agreement is governed by the prevailing laws of Bangladesh.</div></div>

  @if($i->settlement && $i->settlement->status === 'posted')
  <h2>সেটেলমেন্ট বিবরণ (Settlement)</h2>
  <table>
    <tr><th>প্রকল্পের নিট ফলাফল</th><td>{{ (float) $i->settlement->net_result < 0 ? '−' : '' }}৳{{ number_format(abs((float) $i->settlement->net_result), 2) }}</td></tr>
    <tr><th>বিনিয়োগকারীর লাভ / লোকসান</th><td>৳{{ number_format((float) $i->settlement->investor_profit, 2) }} / ৳{{ number_format((float) $i->settlement->investor_loss, 2) }}</td></tr>
    <tr><th>ওয়ালেটে জমাকৃত অর্থ</th><td class="amount">৳{{ number_format((float) $i->settlement->payout, 2) }}</td></tr>
  </table>
  @endif

  <div class="sign">
    <div>
      <div class="line"></div>
      <b>প্রথম পক্ষের পক্ষে</b><br>অনুমোদিত স্বাক্ষরকারী<br>নাম ও পদবি: ____________________<br>তারিখ ও সিল
    </div>
    <div>
      <div class="esign">ইলেকট্রনিক সম্মতি প্রদত্ত<br><b>{{ $i->user->name }}</b><br>{{ $dt($snap['accepted_at']) }}</div>
      <b>দ্বিতীয় পক্ষ (বিনিয়োগকারী)</b><br>অনলাইনে সম্মতির মাধ্যমে স্বাক্ষরিত
    </div>
  </div>

  <div class="foot">
    <div>ডকুমেন্ট যাচাই কোড: <b>{{ $code }}</b><br>প্রস্তুতের সময়: {{ now()->timezone('Asia/Dhaka')->format('d M Y, h:i A') }}</div>
    <div style="max-width:420px">এটি সিস্টেম-জেনারেটেড চুক্তিপত্র। যাচাই কোডটি সংরক্ষিত চুক্তির তথ্য থেকে তৈরি; তথ্য বদলালে কোড বদলে যায়।</div>
  </div>

</div></div>
</body></html>
