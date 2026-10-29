<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void { Schema::table('payment_deposits', fn (Blueprint $t) => $t->string('sender_number', 20)->nullable()->after('reference')); }
    public function down(): void { Schema::table('payment_deposits', fn (Blueprint $t) => $t->dropColumn('sender_number')); }
};
