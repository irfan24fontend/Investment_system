<?php
namespace App\Http\Controllers\Admin;

use App\Exceptions\BusinessRuleException;
use App\Http\Controllers\Controller;
use App\Models\{Investment, InvestmentOffer, Order, PaymentDeposit, Settlement, User, UserNotice, WithdrawalRequest};
use App\Services\{Audit, DepositService, LedgerService, SettlementService, WithdrawalService};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AdminController extends Controller {
    public function dashboard(LedgerService $l) {
        return view('admin.dashboard', [
            'offers' => InvestmentOffer::selectRaw('status, count(*) c')->groupBy('status')->pluck('c', 'status'),
            'pendingDeposits' => PaymentDeposit::where('status', 'pending')->count(),
            'pendingWithdrawals' => WithdrawalRequest::where('status', 'pending')->count(),
            'toVerify' => User::where('role', 'investor')->whereNull('investor_verified_at')->count(),
            'reconcile' => $l->reconcile(),
            'draftOffers' => InvestmentOffer::where('status', 'draft')->count(),
            'fundedOffers' => InvestmentOffer::where('status', 'funded')->count(),
            'preparedSettlements' => Settlement::where('status', 'prepared')->count(),
        ]);
    }

    // ---- Investors
    public function investors() { return view('admin.investors', ['rows' => User::where('role', 'investor')->latest()->paginate(20)]); }
    public function verifyInvestor(Request $r, User $user) {
        abort_unless($user->role === 'investor', 404);
        $d = $r->validate(['payout_account' => 'nullable|regex:/^01[3-9]\d{8}$/']);
        $user->forceFill(['investor_verified_at' => now(), 'payout_account' => $d['payout_account'] ?? $user->payout_account,
            'payout_account_verified_at' => ($d['payout_account'] ?? $user->payout_account) ? now() : null])->save();
        Audit::log('investor.verified', $user);
        UserNotice::to($user->id, 'Account verified', 'You can now invest and withdraw.');
        return back()->with('ok', 'Verified.');
    }

    // ---- Offers (super admin)
    public function offers() { return view('admin.offers', ['rows' => InvestmentOffer::latest()->paginate(20)]); }
    private function offerPayload(Request $r, ?InvestmentOffer $existing = null): array {
        $d = $r->validate(['code' => 'required|max:30|unique:investment_offers,code'.($existing ? ','.$existing->id : ''), 'title' => 'required|max:200',
            'category' => 'required|in:order_financing,stock_financing,bulk_purchase_financing',
            'product_name' => 'required|max:200', 'quantity' => 'required|integer|min:1', 'unit_price' => 'required|numeric|min:0.01',
            'other_expenses' => 'nullable|numeric|min:0', 'business_contribution' => 'nullable|numeric|min:0', 'description' => 'nullable',
            'risk_disclosure' => 'nullable|min:20', 'deadline_at' => 'required|date|after:now', 'estimated_settlement_date' => 'nullable|date|after:today',
            'investor_return_pct' => 'required|numeric|min:0|max:100']);
        $d['description'] = trim((string) ($d['description'] ?? '')) ?: InvestmentOffer::DEFAULT_DESCRIPTION;
        $d['risk_disclosure'] = trim((string) ($d['risk_disclosure'] ?? '')) ?: InvestmentOffer::DEFAULT_RISK; // a risk text is always stored
        // server-computed totals: client can't send inconsistent figures
        $product = bcmul((string) $d['quantity'], number_format((float) $d['unit_price'], 2, '.', ''), 2);
        $total = bcadd($product, number_format((float) ($d['other_expenses'] ?? 0), 2, '.', ''), 2);
        $contrib = number_format((float) ($d['business_contribution'] ?? 0), 2, '.', '');
        if (bccomp($contrib, $total, 2) >= 0) {
            throw \Illuminate\Validation\ValidationException::withMessages(['business_contribution' => "Contribution must be less than total cost (৳{$total})."]);
        }
        $pct = $d['investor_return_pct'];
        unset($d['investor_return_pct']);
        return $d + ['other_expenses' => $d['other_expenses'] ?? 0, 'total_cost' => $total, 'business_contribution' => $contrib,
            'required_amount' => bcsub($total, $contrib, 2), 'settlement_rules' => ['investor_return_pct' => $pct]];
    }

    public function offerStore(Request $r) {
        $o = InvestmentOffer::create($this->offerPayload($r) + ['created_by' => $r->user()->id]);
        Audit::log('offer.created', $o);
        return back()->with('ok', 'Offer saved as draft. Press Publish to make it visible to investors.');
    }

    public function offerEdit(InvestmentOffer $offer) {
        if (! in_array($offer->status, ['draft', 'paused'], true)) return redirect()->route('admin.offers')->withErrors(['action' => 'Only draft or paused offers can be edited. Pause a published offer first.']);
        return view('admin.offer-edit', compact('offer'));
    }

    public function offerUpdate(Request $r, InvestmentOffer $offer) {
        $payload = $this->offerPayload($r, $offer);
        try {
            DB::transaction(function () use ($offer, $payload) {
                $o = InvestmentOffer::whereKey($offer->id)->lockForUpdate()->first();
                if (! in_array($o->status, ['draft', 'paused'], true) || Investment::where('offer_id', $o->id)->whereNotNull('active_offer_id')->exists())
                    throw new BusinessRuleException('Only draft or paused offers without an investor can be edited.');
                $o->update($payload);
                Audit::log('offer.updated', $o);
            });
        } catch (BusinessRuleException $e) { return redirect()->route('admin.offers')->withErrors(['action' => $e->getMessage()]); }
        return redirect()->route('admin.offers')->with('ok', 'Offer updated.');
    }

    public function offerStatus(Request $r, InvestmentOffer $offer) {
        $to = $r->validate(['to' => 'required|in:published,paused,cancelled'])['to'];
        try {
            DB::transaction(function () use ($offer, $to, $r) {
                $o = InvestmentOffer::whereKey($offer->id)->lockForUpdate()->first();
                if ($o->status === 'funded' || Investment::where('offer_id', $o->id)->whereNotNull('active_offer_id')->exists()) throw new BusinessRuleException('This offer is already funded by an investor and cannot be changed.');
                if ($to === 'published' && $o->deadline_at->lte(now())) throw new BusinessRuleException('Deadline has passed. Edit the offer and set a future deadline first.');
                $ok = match ($to) { 'published' => in_array($o->status, ['draft', 'paused'], true),
                    'paused' => $o->status === 'published', 'cancelled' => in_array($o->status, ['draft', 'published', 'paused'], true) };
                if (! $ok) throw new BusinessRuleException("Cannot change status from {$o->status} to {$to}.");
                $o->forceFill(['status' => $to, 'published_by' => $to === 'published' ? $r->user()->id : $o->published_by])->save();
                Audit::log("offer.$to", $o);
            });
        } catch (BusinessRuleException $e) { return back()->withErrors(['action' => $e->getMessage()]); }
        return back()->with('ok', 'Offer is now '.$to.'.');
    }

    // ---- Progress + completion (super admin, any time)
    public function offerProgress(InvestmentOffer $offer) {
        $order = Order::where('offer_id', $offer->id)->first();
        return view('admin.offer-progress', ['offer' => $offer, 'order' => $order,
            'inv' => Investment::with('user', 'settlement')->where('offer_id', $offer->id)->first(),
            'updates' => DB::table('offer_progress')->where('offer_id', $offer->id)->orderByDesc('id')->get(),
            'defaultRevenue' => ($order && bccomp((string) $order->sale_price, '0', 2) > 0) ? (string) $order->sale_price : '',
            'defaultCost' => $order ? bcadd(bcadd((string) $order->purchase_cost, (string) $order->transport_cost, 2), (string) $order->other_cost, 2) : (string) $offer->total_cost]);
    }

    public function offerProgressStore(Request $r, InvestmentOffer $offer) {
        $d = $r->validate(['stage' => 'required|in:note,procurement,ready,delivered,financial_verification', 'percent' => 'required|integer|min:0|max:100', 'note' => 'required|max:1000']);
        $inv = Investment::where('offer_id', $offer->id)->first();
        if (! $inv || $inv->status !== 'confirmed') return back()->withErrors(['action' => 'Progress can be posted only while the investment is active.']);
        DB::table('offer_progress')->insert(['offer_id' => $offer->id, 'stage' => $d['stage'], 'percent' => $d['percent'], 'note' => $d['note'], 'created_by' => $r->user()->id, 'created_at' => now()]);
        $order = Order::where('offer_id', $offer->id)->first();
        if ($order && $d['stage'] !== 'note' && ! in_array($order->status, ['settled', 'cancelled'], true)) {
            $order->forceFill(['status' => $d['stage'], 'delivered_at' => $d['stage'] === 'delivered' ? ($order->delivered_at ?? now()) : $order->delivered_at])->save();
        }
        UserNotice::to($inv->user_id, "Update: {$offer->title}", $d['note']);
        Audit::log('offer.progress', $offer, $d);
        return back()->with('ok', 'Progress update posted. The investor was notified.');
    }

    public function offerComplete(Request $r, InvestmentOffer $offer, SettlementService $s) {
        $d = $r->validate(['revenue' => 'required|numeric|min:0', 'refunds' => 'nullable|numeric|min:0', 'actual_cost' => 'required|numeric|min:0', 'confirm' => 'accepted']);
        $f = fn ($v) => number_format((float) $v, 2, '.', '');
        try { $st = $s->completeOffer($offer, $r->user(), $f($d['revenue']), $f($d['refunds'] ?? 0), $f($d['actual_cost'])); }
        catch (BusinessRuleException $e) { return back()->withErrors(['action' => $e->getMessage()]); }
        return back()->with('ok', "Offer completed. ৳{$st->payout} credited to the investor's wallet.");
    }

    public function depositReceipt(PaymentDeposit $deposit) {
        abort_unless($deposit->receipt_path && Storage::exists($deposit->receipt_path), 404);
        Audit::log('deposit.receipt_viewed', $deposit);
        return Storage::download($deposit->receipt_path);
    }

    // ---- Deposits / withdrawals (finance)
    public function deposits() { return view('admin.deposits', ['rows' => PaymentDeposit::with('user')->latest()->paginate(20)]); }
    public function depositApprove(PaymentDeposit $deposit, DepositService $s, Request $r) { return $this->run(fn () => $s->approve($deposit, $r->user())); }
    public function depositReject(PaymentDeposit $deposit, DepositService $s, Request $r) {
        $reason = $r->validate(['reason' => 'required|max:200'])['reason']; return $this->run(fn () => $s->reject($deposit, $r->user(), $reason));
    }
    public function withdrawals() { return view('admin.withdrawals', ['rows' => WithdrawalRequest::with('user')->latest()->paginate(20)]); }
    public function withdrawalComplete(WithdrawalRequest $w, WithdrawalService $s, Request $r) {
        $ref = $r->validate(['reference' => 'required|max:100'])['reference']; return $this->run(fn () => $s->complete($w, $r->user(), $ref));
    }
    public function withdrawalReject(WithdrawalRequest $w, WithdrawalService $s, Request $r) {
        $reason = $r->validate(['reason' => 'required|max:200'])['reason']; return $this->run(fn () => $s->reject($w, $r->user(), $reason));
    }

    // ---- Settlements
    public function settlements() {
        return view('admin.settlements', ['ready' => Investment::with('offer')->where('status', 'confirmed')->whereDoesntHave('settlement')->get()
            ->filter(fn ($i) => Order::where('offer_id', $i->offer_id)->whereIn('status', ['financial_verification', 'settlement_pending', 'cancelled'])->exists()),
            'pending' => Settlement::with('investment.offer')->where('status', 'prepared')->latest()->get(),
            'rows' => Settlement::with('investment.offer')->latest()->paginate(20)]);
    }
    public function settlementPrepare(Request $r, Investment $investment, SettlementService $s) {
        $d = $r->validate(['revenue' => 'required|numeric|min:0', 'refunds' => 'nullable|numeric|min:0', 'extra' => 'nullable|numeric|min:0']);
        $f = fn ($v) => number_format((float) $v, 2, '.', '');
        return $this->run(fn () => $s->prepare($investment, $r->user(), $f($d['revenue']), $f($d['refunds'] ?? 0), $f($d['extra'] ?? 0)));
    }
    public function settlementRecalculate(Request $r, Settlement $settlement, SettlementService $s) {
        $d = $r->validate(['revenue' => 'required|numeric|min:0', 'refunds' => 'nullable|numeric|min:0', 'extra' => 'nullable|numeric|min:0']);
        $f = fn ($v) => number_format((float) $v, 2, '.', '');
        return $this->run(fn () => $s->recalculate($settlement, $r->user(), $f($d['revenue']), $f($d['refunds'] ?? 0), $f($d['extra'] ?? 0)));
    }
    public function settlementApprove(Settlement $settlement, SettlementService $s, Request $r) { return $this->run(fn () => $s->approve($settlement, $r->user())); }

    // ---- Reports
    public function reports(LedgerService $l) {
        $by = DB::table('ledger_entries as e')->join('ledger_accounts as a', 'a.id', '=', 'e.account_id')->groupBy('a.type')
            ->selectRaw("a.type, SUM(CASE WHEN e.direction='credit' THEN e.amount ELSE -e.amount END) bal")->pluck('bal', 'type');
        $dep = DB::table('ledger_transactions as t')->join('ledger_entries as e', 'e.transaction_id', '=', 't.id')->where('t.type', 'deposit')->where('e.direction', 'credit')->sum('e.amount');
        return view('admin.reports', ['byType' => $by, 'deposited' => $dep, 'reconcile' => $l->reconcile(),
            'settled' => Settlement::where('status', 'posted')->selectRaw('COALESCE(SUM(net_result),0) net, COALESCE(SUM(investor_profit),0) p, COALESCE(SUM(investor_loss),0) l')->first(),
            'activeInvest' => Investment::where('status', 'confirmed')->sum('amount'),
            'expired' => InvestmentOffer::where('status', 'expired')->count(), 'cancelled' => InvestmentOffer::where('status', 'cancelled')->count()]);
    }
    public function exportLedger(Request $r) {
        Audit::log('report.export_ledger');
        return response()->streamDownload(function () {
            $o = fopen('php://output', 'w'); fputcsv($o, ['tx_id', 'uuid', 'type', 'reference', 'account_type', 'user_id', 'direction', 'amount', 'created_at']);
            DB::table('ledger_entries as e')->join('ledger_transactions as t', 't.id', '=', 'e.transaction_id')->join('ledger_accounts as a', 'a.id', '=', 'e.account_id')
                ->orderBy('e.id')->select('t.id', 't.uuid', 't.type', 't.reference', 'a.type as at', 'a.user_id', 'e.direction', 'e.amount', 'e.created_at')
                ->chunk(500, fn ($c) => $c->each(fn ($x) => fputcsv($o, (array) $x)));
            fclose($o);
        }, 'ledger-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv']);
    }
    public function auditLogs() { return view('admin.audit', ['rows' => DB::table('audit_logs')->orderByDesc('id')->paginate(30)]); }

    private function run(\Closure $f) {
        try { $f(); } catch (BusinessRuleException $e) { return back()->withErrors(['action' => $e->getMessage()]); }
        return back()->with('ok', 'Done.');
    }
}
