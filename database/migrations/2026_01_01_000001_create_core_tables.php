<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ledger_accounts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained()->restrictOnDelete();
            $t->string('type', 32); // wallet|invested|withdrawal_reserved|platform_cash|project_escrow
            $t->char('currency', 3)->default('BDT');
            $t->timestamps();
            $t->unique(['user_id', 'type', 'currency']);
        });

        Schema::create('ledger_transactions', function (Blueprint $t) {
            $t->id();
            $t->uuid('uuid')->unique();
            $t->string('type', 32);
            $t->string('idempotency_key', 100)->unique();
            $t->string('reference', 100)->nullable()->index();
            $t->nullableMorphs('related');
            $t->foreignId('initiated_by')->nullable()->constrained('users');
            $t->foreignId('approved_by')->nullable()->constrained('users');
            $t->foreignId('reverses_id')->nullable()->constrained('ledger_transactions');
            $t->string('description')->nullable();
            $t->json('meta')->nullable();
            $t->timestamps();
        });

        Schema::create('ledger_entries', function (Blueprint $t) {
            $t->id();
            $t->foreignId('transaction_id')->constrained('ledger_transactions')->restrictOnDelete();
            $t->foreignId('account_id')->constrained('ledger_accounts')->restrictOnDelete();
            $t->enum('direction', ['debit', 'credit']);
            $t->decimal('amount', 16, 2);
            $t->timestamp('created_at')->useCurrent();
            $t->index(['account_id', 'direction']);
        });

        Schema::create('investment_offers', function (Blueprint $t) {
            $t->id();
            $t->string('code', 30)->unique();
            $t->string('title');
            $t->string('category', 40)->default('order_financing');
            $t->string('product_name');
            $t->unsignedInteger('quantity');
            $t->decimal('unit_price', 14, 2);
            $t->decimal('other_expenses', 14, 2)->default(0);
            $t->decimal('total_cost', 16, 2);
            $t->decimal('business_contribution', 16, 2)->default(0);
            $t->decimal('required_amount', 16, 2);
            $t->text('description')->nullable();
            $t->text('risk_disclosure');
            $t->string('return_model', 40)->default('profit_share');
            $t->json('settlement_rules')->nullable();
            $t->dateTime('opens_at')->nullable();
            $t->dateTime('deadline_at');
            $t->date('estimated_settlement_date')->nullable();
            $t->string('status', 20)->default('draft')->index(); // draft|published|paused|funded|expired|cancelled
            $t->foreignId('created_by')->constrained('users');
            $t->foreignId('published_by')->nullable()->constrained('users');
            $t->timestamps();
        });

        Schema::create('investments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('offer_id')->constrained('investment_offers')->restrictOnDelete();
            $t->foreignId('user_id')->constrained()->restrictOnDelete();
            $t->decimal('amount', 16, 2);
            $t->string('status', 20)->default('confirmed')->index();
            // = offer_id while holding the offer, NULL when released. UNIQUE => one active investor per offer.
            $t->unsignedBigInteger('active_offer_id')->nullable()->unique();
            $t->string('idempotency_key', 100)->unique();
            $t->json('agreement_snapshot');
            $t->dateTime('confirmed_at')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['investments','investment_offers','ledger_entries','ledger_transactions','ledger_accounts'] as $tbl) {
            Schema::dropIfExists($tbl);
        }
    }
};
