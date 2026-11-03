<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('settlements', function (Blueprint $table) {
            $table->unsignedBigInteger('reversal_transaction_id')->nullable()->unique();
            $table->foreign('reversal_transaction_id')->references('id')->on('ledger_transactions')->restrictOnDelete();
            $table->text('reversal_reason')->nullable();
            $table->timestamp('reversed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('settlements', function (Blueprint $table) {
            $table->dropForeign(['reversal_transaction_id']);
            $table->dropUnique(['reversal_transaction_id']);
            $table->dropColumn(['reversal_transaction_id', 'reversal_reason', 'reversed_at']);
        });
    }
};
