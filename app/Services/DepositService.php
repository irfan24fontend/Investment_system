<?php
namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Models\PaymentDeposit;
use App\Models\User;
use App\Models\UserNotice;
use Illuminate\Support\Facades\DB;

/** Manual deposits only (cash + bKash TrxID). Wallet is credited ONLY after staff approval. */
class DepositService {
    public function __construct(private LedgerService $ledger) {}

    public function submitCash(User $u, string $amount, string $reference, ?string $receiptPath): PaymentDeposit {
        return $this->submit($u, 'cash', $amount, trim($reference), null, $receiptPath);
    }

    public function submitBkash(User $u, string $amount, string $trxId, string $senderNumber): PaymentDeposit {
        return $this->submit($u, 'bkash', $amount, strtoupper(trim($trxId)), $senderNumber, null);
    }

    /** Bank deposit: investor transfers to the company's Islami Bank account, then submits the reference + slip. */
    public function submitBank(User $u, string $amount, string $reference, ?string $senderAccount, string $receiptPath): PaymentDeposit {
        return $this->submit($u, 'bank', $amount, strtoupper(trim($reference)), $senderAccount, $receiptPath);
    }

    private function submit(User $u, string $method, string $amount, string $ref, ?string $sender, ?string $receipt): PaymentDeposit {
        if (bccomp($amount, '0', 2) <= 0) throw new BusinessRuleException('Invalid amount.');
        if (PaymentDeposit::where(['method' => $method, 'reference' => $ref])->exists()) throw new BusinessRuleException('This reference/TrxID was already submitted.');
        return PaymentDeposit::create(['user_id' => $u->id, 'method' => $method, 'amount' => $amount, 'reference' => $ref, 'sender_number' => $sender, 'receipt_path' => $receipt]);
    }

    /** Works for cash and bkash. Ledger key makes a double credit impossible, even for the same TrxID. */
    public function approve(PaymentDeposit $d, User $by): void {
        DB::transaction(function () use ($d, $by) {
            $d = PaymentDeposit::whereKey($d->id)->lockForUpdate()->first();
            if ($d->status !== 'pending') throw new BusinessRuleException('Deposit not pending.');
            $key = $d->method === 'bkash' ? "bkash-trx:{$d->reference}" : "{$d->method}:{$d->id}";
            $this->ledger->creditDeposit($d->user_id, number_format((float) $d->amount, 2, '.', ''), $key, $by->id);
            $d->forceFill(['status' => 'confirmed', 'reviewed_by' => $by->id])->save();
            Audit::log('deposit.approved', $d, ['method' => $d->method, 'ref' => $d->reference]);
            UserNotice::to($d->user_id, 'Deposit confirmed', '৳'.number_format($d->amount, 2).' added to your wallet.');
        });
    }

    public function approveCash(PaymentDeposit $d, User $by): void { $this->approve($d, $by); } // back-compat

    public function reject(PaymentDeposit $d, User $by, string $reason): void {
        DB::transaction(function () use ($d, $by, $reason) {
            $d = PaymentDeposit::whereKey($d->id)->lockForUpdate()->first();
            if ($d->status !== 'pending') throw new BusinessRuleException('Deposit not pending.');
            $d->forceFill(['status' => 'rejected', 'rejection_reason' => $reason, 'reviewed_by' => $by->id])->save();
            Audit::log('deposit.rejected', $d, ['reason' => $reason]);
            UserNotice::to($d->user_id, 'Deposit rejected', $reason);
        });
    }
}
