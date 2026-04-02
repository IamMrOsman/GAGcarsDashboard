<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Production-safe: earlier code created `wallet_ledger` (singular) but the app expects `wallet_ledgers`.
        if (Schema::hasTable('wallet_ledger') && !Schema::hasTable('wallet_ledgers')) {
            Schema::rename('wallet_ledger', 'wallet_ledgers');
        }

        // If neither table exists (e.g. migrations never ran on this environment), create the expected table.
        if (!Schema::hasTable('wallet_ledgers')) {
            Schema::create('wallet_ledgers', function (Blueprint $table) {
                $table->ulid('id');

                $table->foreignUlid('user_id')
                    ->constrained('users')
                    ->cascadeOnDelete();

                $table->string('direction', 10);
                $table->decimal('amount', 10, 2);
                $table->string('reason', 50)->nullable();
                $table->string('reference')->unique();
                $table->string('status', 30)->default('pending');
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('wallet_ledgers') && !Schema::hasTable('wallet_ledger')) {
            Schema::rename('wallet_ledgers', 'wallet_ledger');
            return;
        }

        Schema::dropIfExists('wallet_ledgers');
    }
};

