<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('sku', 50)->nullable()->unique();
            $table->string('name', 200);
            $table->decimal('unit_cost', 14, 2);
            $table->decimal('unit_price', 14, 2);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('customer_orders', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('customer_name', 150);
            $table->string('customer_phone', 30);
            $table->string('whatsapp_number', 30)->nullable();
            $table->text('delivery_address')->nullable();
            $table->string('status', 30)->default('received')->index();
            $table->decimal('subtotal', 16, 2)->default(0);
            $table->decimal('delivery_charge', 14, 2)->default(0);
            $table->decimal('delivery_cost', 14, 2)->default(0);
            $table->decimal('other_cost', 14, 2)->default(0);
            $table->decimal('total_amount', 16, 2)->default(0);
            $table->decimal('total_cost', 16, 2)->default(0);
            $table->decimal('profit', 16, 2)->default(0);
            $table->decimal('paid_amount', 16, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('customer_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name', 200);
            $table->unsignedInteger('quantity');
            $table->decimal('unit_cost', 14, 2);
            $table->decimal('unit_price', 14, 2);
            $table->decimal('line_cost', 16, 2);
            $table->decimal('line_total', 16, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_order_items');
        Schema::dropIfExists('customer_orders');
        Schema::dropIfExists('products');
    }
};
