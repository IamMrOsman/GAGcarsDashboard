<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_topups', function (Blueprint $table) {
            $table->ulid('id');

            $table->foreignUlid('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Paystack reference for the top-up transaction
            $table->string('reference')->unique();

            $table->string('status', 30)->default('initialized');
            $table->decimal('amount', 10, 2);

            $table->dateTime('paid_at')->nullable();

            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_topups');
    }
};

