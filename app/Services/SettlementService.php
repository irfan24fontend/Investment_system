<?php
namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Models\Investment;
use App\Models\Order;
use App\Models\Settlement;
use App\Models\User;
use App\Models\UserNotice;
use Illuminate\Support\Facades\DB;

/**
 * Model A (new, settlement_rules.investor_return_pct): target = invested x pct%. Investor profit = min(target, actual net profit).
 *   If the project makes less than the target, investor gets only what was really earned. Never more than actual profit.
 * Model B (old offers, settlement_rules.investor_profit_share_pct): investor gets net x share%.
 * Both: loss is shared by funding ratio.
 *   net      = revenue_received - refunds - (purchase + transport + other costs) - extra_deductions
 *   profit   : investor gets net * share% (if net > 0)
 *   loss     : investor bears net_loss * (investment / total_project_cost), capped at invested amount
 *   payout   = invested + profit - loss
 * Revenue is NEVER paid out as profit. Maker (prepare) and checker (approve) must be different people.
 */
class SettlementService {
    public function __construct(private LedgerService $ledger) {}

    public function prepare(Investment $inv, User $by, string $revenue, string $refunds = '0.00', string $extraDeductions = '0.00'): Settlement {
        return DB::transaction(function () use ($inv, $by, $revenue, $refunds, $extraDeductions) {
            $inv = Investment::whereKey($inv->id)->lockForUpdate()->first();
            if ($inv->status !== 'confirmed') throw new BusinessRuleException('Investment already settled or inactive.');
            if (Settlement::where('investment_id', $inv->id)->exists()) throw new BusinessRuleException('Settlement already exists.');
            $order = Order::where('offer_id', $inv->offer_id)->first() ?? throw new BusinessRuleException('No order linked to this offer.');
            if (! in_array($order->status, ['financial_verification', 'settlement_pending', 'cancelled'], true))
                throw new BusinessRuleException('Order is not ready for settlement.');
            foreach ([$revenue, $refunds, $extraDeductions] as $v) if (bccomp($v, '0', 2) < 0) throw new BusinessRuleException('Negative inputs not allowed.');

            $c = $this->calc($inv, $order, $revenue, $refunds, $extraDeductions);
            return Settlement::create([
                'investment_id' => $inv->id, 'order_id' => $order->id, 'revenue_received' => $revenue, 'refunds' => $refunds,
                'total_costs' => $c['costs'], 'net_result' => $c['net'], 'investor_profit' => $c['profit'], 'investor_loss' => $c['loss'], 'payout' => $c['payout'],
                'breakdown' => $c['breakdown'], 'prepared_by' => $by->id,
            ]);
        });
    }

    /** Pure calculation, shared by prepare() and recalculate(). Writes nothing. */
    private function calc(Investment $inv, Order $order, string $revenue, string $refunds, string $extraDeductions): array {
        $offer = $inv->offer;
        $costs = bcadd(bcadd(bcadd((string) $order->purchase_cost, (string) $order->transport_cost, 2), (string) $order->other_cost, 2), $extraDeductions, 2);
        $net = bcsub(bcsub($revenue, $refunds, 2), $costs, 2);
        $amount = number_format((float) $inv->amount, 2, '.', '');
        $share = (string) (($offer->settlement_rules['investor_profit_share_pct'] ?? 0));
        $profit = '0.00'; $loss = '0.00';

        $returnPct = $offer->settlement_rules['investor_return_pct'] ?? null;
        $target = null;
        if (bccomp($net, '0', 2) > 0) {
            if ($returnPct !== null) {
                $target = bcdiv(bcmul($amount, (string) $returnPct, 4), '100', 2);
                $profit = bccomp($net, $target, 2) < 0 ? bcadd($net, '0', 2) : $target;
            } else {
                $profit = bcdiv(bcmul($net, $share, 4), '100', 2);
            }
        } elseif (bccomp($net, '0', 2) < 0) {
            $ratio = bcdiv($amount, (string) max((float) $offer->total_cost, 0.01), 8);
            $loss = bcmul(bcsub('0', $net, 2), $ratio, 2);
            if (bccomp($loss, $amount, 2) > 0) $loss = $amount;
        }
        $payout = bcsub(bcadd($amount, $profit, 2), $loss, 2);
        $breakdown = compact('revenue', 'refunds', 'costs', 'net', 'share', 'returnPct', 'target', 'amount', 'extraDeductions');
        return compact('costs', 'net', 'profit', 'loss', 'payout', 'breakdown');
    }

    /**
     * Fix a wrong calculation BEFORE approval. Same settlement row is updated (one settlement per investment),
     * the person who recalculates becomes the preparer, so maker-checker still applies. Posted settlements are final.
     */
    public function recalculate(Settlement $s, User $by, string $revenue, string $refunds = '0.00', string $extraDeductions = '0.00'): Settlement {
        return DB::transaction(function () use ($s, $by, $revenue, $refunds, $extraDeductions) {
            $s = Settlement::whereKey($s->id)->lockForUpdate()->first();
            if ($s->status !== 'prepared') throw new BusinessRuleException('Only a settlement that is not yet approved can be recalculated.');
            $inv = Investment::whereKey($s->investment_id)->lockForUpdate()->first();
            if ($inv->status !== 'confirmed') throw new BusinessRuleException('Investment already settled or inactive.');
            $order = Order::findOrFail($s->order_id);
            foreach ([$revenue, $refunds, $extraDeductions] as $v) if (bccomp($v, '0', 2) < 0) throw new BusinessRuleException('Negative inputs not allowed.');

            $old = $s->only(['revenue_received', 'refunds', 'total_costs', 'net_result', 'investor_profit', 'investor_loss', 'payout', 'prepared_by']);
            $c = $this->calc($inv, $order, $revenue, $refunds, $extraDeductions);
            $c['breakdown']['previous'] = $old;
            $s->forceFill([
                'revenue_received' => $revenue, 'refunds' => $refunds, 'total_costs' => $c['costs'], 'net_result' => $c['net'],
                'investor_profit' => $c['profit'], 'investor_loss' => $c['loss'], 'payout' => $c['payout'],
                'breakdown' => $c['breakdown'], 'prepared_by' => $by->id,
            ])->save();
            Audit::log('settlement.recalculated', $s, ['old_payout' => $old['payout'], 'new_payout' => $c['payout']]);
            return $s;
        });
    }

    public function approve(Settlement $s, User $approver): void {
        DB::transaction(function () use ($s, $approver) {
            $s = Settlement::whereKey($s->id)->lockForUpdate()->first();
            if ($s->status !== 'prepared') throw new BusinessRuleException('Settlement already processed.');
            if ($s->prepared_by === $approver->id && ! $approver->hasRole('super_admin')) throw new BusinessRuleException('Preparer cannot approve own settlement.');
            $inv = Investment::whereKey($s->investment_id)->lockForUpdate()->first();
            if ($inv->status !== 'confirmed') throw new BusinessRuleException('Investment not active.');

            $amount = number_format((float) $inv->amount, 2, '.', '');
            $invested = $this->ledger->account($inv->user_id, 'invested');
            $wallet = $this->ledger->account($inv->user_id, 'wallet');
            $result = $this->ledger->account(null, 'project_result');
            $lines = [[$invested, 'debit', $amount]];
            if (bccomp($s->payout, '0', 2) > 0) $lines[] = [$wallet, 'credit', $s->payout];
            if (bccomp($s->investor_profit, '0', 2) > 0) $lines[] = [$result, 'debit', $s->investor_profit];
            if (bccomp($s->investor_loss, '0', 2) > 0) $lines[] = [$result, 'credit', $s->investor_loss];

            $tx = $this->ledger->post('settlement', "settlement:{$s->id}", $lines, [
                'related_type' => Settlement::class, 'related_id' => $s->id, 'initiated_by' => $s->prepared_by, 'approved_by' => $approver->id,
            ]);
            $s->forceFill(['status' => 'posted', 'approved_by' => $approver->id, 'ledger_transaction_id' => $tx])->save();
            $inv->forceFill(['status' => bccomp($s->investor_loss, '0', 2) > 0 ? 'loss_settled' : 'settled'])->save();
            Order::whereKey($s->order_id)->update(['status' => 'settled']);
            \App\Models\InvestmentOffer::whereKey($inv->offer_id)->update(['status' => 'completed']);
            Audit::log('settlement.posted', $s, ['payout' => $s->payout]);
            UserNotice::to($inv->user_id, 'Settlement approved', "৳{$s->payout} credited to your wallet.");
        });
    }

    /**
     * Super admin completes an offer at any time: actual revenue + actual cost are entered, the system calculates
     * profit/loss under the accepted agreement, posts it to the ledger, and credits principal +/- result to the wallet.
     */
    public function completeOffer(\App\Models\InvestmentOffer $offer, User $admin, string $revenue, string $refunds, string $actualCost): Settlement {
        if (! $admin->hasRole('super_admin')) throw new BusinessRuleException('Only the super admin can complete an offer.');
        if (bccomp($actualCost, '0', 2) < 0) throw new BusinessRuleException('Negative cost not allowed.');
        return DB::transaction(function () use ($offer, $admin, $revenue, $refunds, $actualCost) {
            $offer = \App\Models\InvestmentOffer::whereKey($offer->id)->lockForUpdate()->first();
            $inv = Investment::where('offer_id', $offer->id)->first() ?? throw new BusinessRuleException('This offer has no investor yet.');
            if ($inv->status !== 'confirmed') throw new BusinessRuleException('This offer is already completed.');

            $order = Order::where('offer_id', $offer->id)->lockForUpdate()->first();
            if (! $order) {
                $order = new Order(['code' => 'ORD-'.$offer->code, 'product_name' => $offer->product_name, 'quantity' => $offer->quantity, 'purchase_cost' => 0]);
                $order->created_by = $admin->id; $order->offer_id = $offer->id;
            }
            $extra = bcadd((string) ($order->transport_cost ?? '0'), (string) ($order->other_cost ?? '0'), 2);
            if (bccomp($actualCost, $extra, 2) >= 0) { $order->purchase_cost = bcsub($actualCost, $extra, 2); }
            else { $order->purchase_cost = '0.00'; $order->transport_cost = $actualCost; $order->other_cost = '0.00'; }
            if ($order->status !== 'cancelled') $order->status = 'financial_verification';
            $order->delivered_at ??= now();
            $order->save();

            $settlement = $this->prepare($inv, $admin, $revenue, $refunds, '0.00');
            $this->approve($settlement, $admin);
            Audit::log('offer.completed', $offer, ['revenue' => $revenue, 'refunds' => $refunds, 'actual_cost' => $actualCost, 'payout' => $settlement->payout]);
            return $settlement->fresh();
        });
    }
}
