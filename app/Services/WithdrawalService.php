<?php
namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Models\User;
use App\Models\UserNotice;
use App\Models\WithdrawalRequest;
use Illuminate\Support\Facades\DB;

class WithdrawalService {
    public function __construct(private LedgerService $ledger) {}

    /** $dest = ['method'=>'bank|bkash','bank_name'=>?,'account_name'=>?,'account_number'=>...] */
    public function request(User $u, string $amount, string $key, ?array $dest = null): WithdrawalRequest {
        return DB::transaction(function () use ($u, $amount, $key, $dest) {
            if ($e = WithdrawalRequest::where('idempotency_key', $key)->where('user_id', $u->id)->first()) return $e;
            if (! $u->investor_verified_at) throw new BusinessRuleException('Identity verification required before withdrawing.');
            $dest ??= ['method' => 'bkash', 'bank_name' => null, 'account_name' => $u->name, 'account_number' => $u->payout_account];
            if (empty($dest['account_number'])) throw new BusinessRuleException('Destination account number required.');
            if (($dest['method'] ?? '') === 'bank' && empty($dest['bank_name'])) throw new BusinessRuleException('Bank name required.');
            if (bccomp($amount, '100', 2) < 0) throw new BusinessRuleException('Minimum withdrawal is ৳100.');

            $wallet = $this->ledger->account($u->id, 'wallet');
            DB::table('ledger_accounts')->where('id', $wallet->id)->lockForUpdate()->first();
            if (bccomp($wallet->balance(true), $amount, 2) < 0) throw new BusinessRuleException('Amount exceeds available balance.');

            $w = WithdrawalRequest::create(['user_id' => $u->id, 'amount' => $amount, 'method' => $dest['method'], 'bank_name' => $dest['bank_name'] ?? null, 'account_name' => $dest['account_name'] ?? null, 'destination' => $dest['account_number'], 'idempotency_key' => $key]);
            $this->ledger->post('withdrawal_reserve', "wd-reserve:{$w->id}", [
                [$wallet, 'debit', $amount], [$this->ledger->account($u->id, 'withdrawal_reserved'), 'credit', $amount],
            ], ['related_type' => WithdrawalRequest::class, 'related_id' => $w->id, 'initiated_by' => $u->id]);
            return $w;
        });
    }

    public function complete(WithdrawalRequest $w, User $by, string $paymentRef): void {
        DB::transaction(function () use ($w, $by, $paymentRef) {
            $w = WithdrawalRequest::whereKey($w->id)->lockForUpdate()->first();
            if ($w->status !== 'pending') throw new BusinessRuleException('Request not pending.');
            $amt = number_format((float) $w->amount, 2, '.', '');
            $this->ledger->post('withdrawal_complete', "wd-complete:{$w->id}", [
                [$this->ledger->account($w->user_id, 'withdrawal_reserved'), 'debit', $amt],
                [$this->ledger->account(null, 'platform_cash'), 'credit', $amt],
            ], ['related_type' => WithdrawalRequest::class, 'related_id' => $w->id, 'initiated_by' => $w->user_id, 'approved_by' => $by->id, 'reference' => $paymentRef]);
            $w->forceFill(['status' => 'completed', 'payment_reference' => $paymentRef, 'reviewed_by' => $by->id])->save();
            Audit::log('withdrawal.completed', $w, ['ref' => $paymentRef]);
            UserNotice::to($w->user_id, 'Withdrawal completed', "৳{$amt} sent. Ref: {$paymentRef}");
        });
    }

    public function reject(WithdrawalRequest $w, User $by, string $reason): void {
        DB::transaction(function () use ($w, $by, $reason) {
            $w = WithdrawalRequest::whereKey($w->id)->lockForUpdate()->first();
            if ($w->status !== 'pending') throw new BusinessRuleException('Request not pending.');
            $amt = number_format((float) $w->amount, 2, '.', '');
            $this->ledger->post('withdrawal_release', "wd-release:{$w->id}", [
                [$this->ledger->account($w->user_id, 'withdrawal_reserved'), 'debit', $amt],
                [$this->ledger->account($w->user_id, 'wallet'), 'credit', $amt],
            ], ['related_type' => WithdrawalRequest::class, 'related_id' => $w->id, 'approved_by' => $by->id]);
            $w->forceFill(['status' => 'rejected', 'admin_note' => $reason, 'reviewed_by' => $by->id])->save();
            Audit::log('withdrawal.rejected', $w, ['reason' => $reason]);
            UserNotice::to($w->user_id, 'Withdrawal rejected', $reason);
        });
    }
}
