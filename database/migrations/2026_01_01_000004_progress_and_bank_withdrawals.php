<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('offer_progress', function (Blueprint $t) {
            $t->id();
            $t->foreignId('offer_id')->constrained('investment_offers')->restrictOnDelete();
            $t->string('stage', 30)->default('note'); // note|procurement|ready|delivered|financial_verification
            $t->unsignedTinyInteger('percent')->default(0);
            $t->text('note');
            $t->foreignId('created_by')->constrained('users');
            $t->timestamp('created_at')->useCurrent();
            $t->index(['offer_id', 'id']);
        });

        Schema::table('withdrawal_requests', function (Blueprint $t) {
            $t->string('bank_name', 100)->nullable()->after('method');
            $t->string('account_name', 100)->nullable()->after('bank_name');
        });
    }

    public function down(): void
    {
        Schema::table('withdrawal_requests', fn (Blueprint $t) => $t->dropColumn(['bank_name', 'account_name']));
        Schema::dropIfExists('offer_progress');
    }
};
