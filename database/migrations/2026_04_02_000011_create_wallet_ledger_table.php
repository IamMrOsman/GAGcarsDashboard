<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_ledgers', function (Blueprint $table) {
            $table->ulid('id');

            $table->foreignUlid('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // credit increases the balance, debit decreases the balance
            $table->string('direction', 10);
            $table->decimal('amount', 10, 2);
            $table->string('reason', 50)->nullable();

            // Used for idempotency with gateway references / generated references
            $table->string('reference')->unique();

            $table->string('status', 30)->default('pending');
            $table->json('metadata')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_ledgers');
    }
};

