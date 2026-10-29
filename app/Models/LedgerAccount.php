<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class LedgerAccount extends Model
{
    protected $guarded = ['id'];

    /** Liability accounts: credits - debits. platform_cash (asset): debits - credits. */
    public function balance(bool $forUpdate = false): string
    {
        // $forUpdate = true forces a CURRENT read (not the transaction's old snapshot). Use it for every
        // balance check that precedes spending money, otherwise MySQL REPEATABLE READ can show a stale
        // balance and allow a double spend.
        $q = DB::table('ledger_entries')->where('account_id', $this->id)
            ->selectRaw("COALESCE(SUM(CASE WHEN direction='credit' THEN amount ELSE -amount END),0) b");
        if ($forUpdate) $q->lockForUpdate();
        $r = $q->first();
        $b = (string) $r->b;
        return $this->type === 'platform_cash' ? bcmul($b, '-1', 2) : bcadd($b, '0', 2);
    }
}
