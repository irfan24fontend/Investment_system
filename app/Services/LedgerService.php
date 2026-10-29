<?php
namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Models\LedgerAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LedgerService
{
    public function account(?int $userId, string $type): LedgerAccount
    {
        return LedgerAccount::firstOrCreate(['user_id' => $userId, 'type' => $type, 'currency' => 'BDT']);
    }

    /**
     * Post a balanced transaction. $lines = [[LedgerAccount, 'debit'|'credit', '100.00'], ...]
     * Idempotent by key. Call inside DB::transaction.
     */
    public function post(string $type, string $key, array $lines, array $opts = []): int
    {
        if ($id = DB::table('ledger_transactions')->where('idempotency_key', $key)->value('id')) {
            return $id;
        }
        $d = '0.00'; $c = '0.00';
        foreach ($lines as [$acc, $dir, $amt]) {
            if (bccomp($amt, '0', 2) <= 0) throw new BusinessRuleException('Amount must be positive.');
            $dir === 'debit' ? $d = bcadd($d, $amt, 2) : $c = bcadd($c, $amt, 2);
        }
        if (bccomp($d, $c, 2) !== 0) throw new BusinessRuleException('Unbalanced ledger transaction.');

        $txId = DB::table('ledger_transactions')->insertGetId([
            'uuid' => (string) Str::uuid(), 'type' => $type, 'idempotency_key' => $key,
            'reference' => $opts['reference'] ?? null,
            'related_type' => $opts['related_type'] ?? null, 'related_id' => $opts['related_id'] ?? null,
            'initiated_by' => $opts['initiated_by'] ?? null, 'approved_by' => $opts['approved_by'] ?? null,
            'description' => $opts['description'] ?? null,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        foreach ($lines as [$acc, $dir, $amt]) {
            DB::table('ledger_entries')->insert(['transaction_id' => $txId, 'account_id' => $acc->id,
                'direction' => $dir, 'amount' => $amt, 'created_at' => now()]);
        }
        return $txId;
    }

    /** Call only after provider verification / finance approval. */
    public function creditDeposit(int $userId, string $amount, string $providerRef, ?int $approverId = null): int
    {
        return DB::transaction(function () use ($userId, $amount, $providerRef, $approverId) {
            $wallet = $this->account($userId, 'wallet');
            $cash = $this->account(null, 'platform_cash');
            return $this->post('deposit', "deposit:$providerRef", [
                [$cash, 'debit', $amount], [$wallet, 'credit', $amount],
            ], ['reference' => $providerRef, 'initiated_by' => $userId, 'approved_by' => $approverId]);
        });
    }

    /** Total debits - credits across the whole ledger. Must always be 0. */
    public function reconcile(): string
    {
        $r = DB::table('ledger_entries')->selectRaw(
            "COALESCE(SUM(CASE WHEN direction='debit' THEN amount ELSE -amount END),0) diff")->first();
        return bcadd((string) $r->diff, '0', 2);
    }
}
