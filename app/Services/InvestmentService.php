<?php
namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Models\Investment;
use App\Models\InvestmentOffer;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class InvestmentService
{
    public function __construct(private LedgerService $ledger) {}

    public function invest(User $user, int $offerId, string $idempotencyKey): Investment
    {
        try {
            return DB::transaction(function () use ($user, $offerId, $idempotencyKey) {
                if ($ex = Investment::where('idempotency_key', $idempotencyKey)->where('user_id', $user->id)->first()) {
                    return $ex; // replayed request
                }
                if (empty($user->investor_verified_at)) {
                    throw new BusinessRuleException('Identity verification required.');
                }

                // Row lock: concurrent requests queue here.
                $offer = InvestmentOffer::whereKey($offerId)->lockForUpdate()->first()
                    ?? throw new BusinessRuleException('Offer not found.');

                if ($offer->status !== 'published') throw new BusinessRuleException('Offer is not available.');
                if ($offer->deadline_at->lte(now())) throw new BusinessRuleException('Offer deadline has passed.');
                if ($offer->opens_at && $offer->opens_at->gt(now())) throw new BusinessRuleException('Offer not open yet.');
                if (Investment::where('offer_id', $offer->id)->whereNotNull('active_offer_id')->exists()) {
                    throw new BusinessRuleException('Offer already funded.');
                }

                $amount = number_format((float) $offer->required_amount, 2, '.', '');
                $wallet = $this->ledger->account($user->id, 'wallet');
                DB::table('ledger_accounts')->where('id', $wallet->id)->lockForUpdate()->first();
                if (bccomp($wallet->balance(true), $amount, 2) < 0) {
                    throw new BusinessRuleException('Insufficient wallet balance.');
                }

                $inv = Investment::create([
                    'offer_id' => $offer->id, 'user_id' => $user->id, 'amount' => $amount,
                    'status' => 'confirmed', 'active_offer_id' => $offer->id,
                    'idempotency_key' => $idempotencyKey, 'confirmed_at' => now(),
                    'agreement_snapshot' => [
                        'offer_code' => $offer->code, 'title' => $offer->title, 'category' => $offer->category,
                        'product' => $offer->product_name, 'quantity' => $offer->quantity,
                        'total_cost' => (string) $offer->total_cost, 'business_contribution' => (string) $offer->business_contribution,
                        'amount' => $amount, 'deadline_at' => $offer->deadline_at->toIso8601String(),
                        'estimated_settlement_date' => $offer->estimated_settlement_date ? (string) $offer->estimated_settlement_date : null,
                        'return_model' => $offer->return_model, 'target_profit' => $offer->targetProfit(), 'rules' => $offer->settlement_rules,
                        'description' => $offer->description,
                        'risk' => $offer->risk_disclosure, 'accepted_at' => now()->toIso8601String(),
                    ],
                ]);

                $this->ledger->post('investment', "investment:{$inv->id}", [
                    [$wallet, 'debit', $amount],
                    [$this->ledger->account($user->id, 'invested'), 'credit', $amount],
                ], ['related_type' => Investment::class, 'related_id' => $inv->id, 'initiated_by' => $user->id]);

                $offer->forceFill(['status' => 'funded'])->save(); // 'status' is guarded: update() would silently ignore it
                \App\Models\Order::where('offer_id', $offer->id)->whereIn('status', ['draft', 'confirmed', 'awaiting_financing'])->update(['status' => 'financing_secured']);
                Audit::log('investment.confirmed', $inv, ['offer' => $offer->code, 'amount' => $amount]);
                return $inv;
            });
        } catch (UniqueConstraintViolationException) {
            throw new BusinessRuleException('Offer already funded.'); // DB-level safety net
        }
    }
}
