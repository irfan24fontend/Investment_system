<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->string('role', 30)->default('investor')->index(); // super_admin|finance_manager|order_manager|investor
            $t->string('phone', 20)->nullable();
            $t->string('nid_number')->nullable();
            $t->timestamp('investor_verified_at')->nullable();
            $t->string('payout_account', 30)->nullable();           // bKash number
            $t->timestamp('payout_account_verified_at')->nullable();
        });

        Schema::create('orders', function (Blueprint $t) {
            $t->id();
            $t->string('code', 30)->unique();
            $t->string('customer_ref')->nullable();
            $t->string('supplier')->nullable();
            $t->string('product_name');
            $t->unsignedInteger('quantity');
            $t->decimal('purchase_cost', 16, 2)->default(0);
            $t->decimal('sale_price', 16, 2)->default(0);
            $t->decimal('transport_cost', 16, 2)->default(0);
            $t->decimal('other_cost', 16, 2)->default(0);
            $t->string('delivery_destination')->nullable();
            $t->date('expected_delivery_date')->nullable();
            $t->timestamp('delivered_at')->nullable();
            // draft|confirmed|awaiting_financing|financing_secured|procurement|ready|delivered|financial_verification|settlement_pending|settled|cancelled|disputed
            $t->string('status', 30)->default('draft')->index();
            $t->foreignId('offer_id')->nullable()->unique()->constrained('investment_offers');
            $t->foreignId('created_by')->constrained('users');
            $t->timestamps();
        });

        Schema::create('payment_deposits', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained();
            $t->string('method', 20); // bkash|cash
            $t->decimal('amount', 16, 2);
            $t->string('reference', 100)->nullable();     // user-entered (cash)
            $t->string('provider_ref', 100)->nullable();  // bKash paymentID
            $t->string('receipt_path')->nullable();
            $t->string('status', 20)->default('pending')->index(); // pending|confirmed|rejected|failed
            $t->string('rejection_reason')->nullable();
            $t->foreignId('reviewed_by')->nullable()->constrained('users');
            $t->timestamps();
            $t->unique(['method', 'provider_ref']);
            $t->unique(['method', 'reference']);
        });

        Schema::create('withdrawal_requests', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained();
            $t->decimal('amount', 16, 2);
            $t->string('method', 20)->default('bkash');
            $t->string('destination', 30);
            $t->string('status', 20)->default('pending')->index(); // pending|completed|rejected
            $t->string('idempotency_key', 100)->unique();
            $t->string('payment_reference')->nullable();
            $t->string('admin_note')->nullable();
            $t->foreignId('reviewed_by')->nullable()->constrained('users');
            $t->timestamps();
        });

        Schema::create('settlements', function (Blueprint $t) {
            $t->id();
            $t->foreignId('investment_id')->unique()->constrained();
            $t->foreignId('order_id')->constrained();
            $t->decimal('revenue_received', 16, 2);
            $t->decimal('refunds', 16, 2)->default(0);
            $t->decimal('total_costs', 16, 2);
            $t->decimal('net_result', 16, 2);          // may be negative
            $t->decimal('investor_profit', 16, 2)->default(0);
            $t->decimal('investor_loss', 16, 2)->default(0);
            $t->decimal('payout', 16, 2);              // amount credited to investor wallet
            $t->json('breakdown');
            $t->string('status', 20)->default('prepared')->index(); // prepared|posted|rejected
            $t->foreignId('prepared_by')->constrained('users');
            $t->foreignId('approved_by')->nullable()->constrained('users');
            $t->unsignedBigInteger('ledger_transaction_id')->nullable();
            $t->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained();
            $t->string('action', 60)->index();
            $t->nullableMorphs('subject');
            $t->json('meta')->nullable();
            $t->string('ip', 45)->nullable();
            $t->timestamp('created_at')->useCurrent();
        });

        Schema::create('user_notices', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('title');
            $t->text('body')->nullable();
            $t->timestamp('read_at')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['user_notices','audit_logs','settlements','withdrawal_requests','payment_deposits','orders'] as $x) Schema::dropIfExists($x);
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['role','phone','nid_number','investor_verified_at','payout_account','payout_account_verified_at']));
    }
};
