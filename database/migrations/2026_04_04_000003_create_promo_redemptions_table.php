<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promo_redemptions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('promo_code_id')->constrained('promo_codes')->cascadeOnDelete();
            $table->foreignUlid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUlid('item_id')->nullable()->constrained('items')->nullOnDelete();
            $table->foreignUlid('transaction_id')->nullable()->unique()->constrained('transactions')->nullOnDelete();
            $table->decimal('gross_amount', 12, 2);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2);
            $table->string('currency', 8)->default('GHS');
            $table->timestamps();

            $table->index(['promo_code_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promo_redemptions');
    }
};
