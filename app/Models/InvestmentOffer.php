<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class InvestmentOffer extends Model
{
    public const DEFAULT_DESCRIPTION = 'এই অফারে আপনার বিনিয়োগ একটি নির্দিষ্ট ব্যবসায়িক কাজে (অর্ডার, স্টক বা পাইকারি ক্রয়) ব্যবহার করা হবে। পণ্য কেনা, পরিবহন ও সংশ্লিষ্ট খরচ মেটাতে অর্থ ব্যবহৃত হবে। কাজ সম্পন্ন হওয়ার পর প্রকৃত আয় থেকে প্রকৃত খরচ বাদ দিয়ে ফলাফল হিসাব করা হবে এবং আপনার গৃহীত চুক্তি অনুযায়ী আপনার ওয়ালেটে সেটেলমেন্ট করা হবে।';

    public const DEFAULT_RISK = 'এই বিনিয়োগের ফলাফল অর্ডারটির প্রকৃত ফলাফলের ওপর নির্ভর করে, তাই লাভের নিশ্চয়তা নেই। কাস্টমারের পেমেন্ট বা ডেলিভারিতে দেরি হলে সেটেলমেন্টও দেরি হতে পারে। কোনো কারণে অর্ডারে লোকসান হলে আপনি বিনিয়োগের চেয়ে কম ফেরত পেতে পারেন। ডেলিভারি, পেমেন্ট ও খরচ যাচাইয়ের পর কোম্পানির অনুমোদন হলেই সেটেলমেন্ট করা হবে।';

    protected $guarded = ['id', 'status', 'published_by'];
    protected $casts = ['deadline_at' => 'datetime', 'opens_at' => 'datetime', 'estimated_settlement_date' => 'date', 'settlement_rules' => 'array'];

    /** Investor return % on the invested amount (new model), or null for old profit-share offers. */
    public function returnPct(): ?string
    {
        $v = $this->settlement_rules['investor_return_pct'] ?? null;
        return $v === null ? null : (string) $v;
    }

    /** Target profit in taka = required_amount x return% (NOT guaranteed, paid only from actual project profit). */
    public function targetProfit(): ?string
    {
        $p = $this->returnPct();
        return $p === null ? null : bcdiv(bcmul(number_format((float) $this->required_amount, 2, '.', ''), $p, 4), '100', 2);
    }

    public function investment() { return $this->hasOne(Investment::class, 'offer_id')->whereNotNull('active_offer_id'); }

    /** Offers an investor may see (server-side filter). */
    public function scopeAvailable($q)
    {
        return $q->where('status', 'published')
            ->where('deadline_at', '>', now())
            ->where(fn ($w) => $w->whereNull('opens_at')->orWhere('opens_at', '<=', now()))
            ->whereDoesntHave('investment');
    }
}
