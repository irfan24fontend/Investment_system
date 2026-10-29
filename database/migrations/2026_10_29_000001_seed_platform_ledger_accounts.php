<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Platform-owned ledger accounts have user_id = NULL. MySQL UNIQUE(user_id,type,currency) does NOT
 * stop duplicate NULL rows, so under concurrency firstOrCreate() could create two platform_cash accounts
 * and split the balance. Create them once here; LedgerService::account() then only ever finds them.
 */
return new class extends Migration {
    public function up(): void
    {
        foreach (['platform_cash', 'project_result'] as $type) {
            $exists = DB::table('ledger_accounts')->whereNull('user_id')->where('type', $type)->where('currency', 'BDT')->exists();
            if (! $exists) {
                DB::table('ledger_accounts')->insert(['user_id' => null, 'type' => $type, 'currency' => 'BDT', 'created_at' => now(), 'updated_at' => now()]);
            }
        }
    }

    public function down(): void {}
};
