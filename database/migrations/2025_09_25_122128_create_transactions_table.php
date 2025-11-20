<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->ulid();
			$table->foreignUlid('user_id')->constrained('users')->cascadeOnDelete();
			$table->foreignUlid('package_id')->constrained('packages');
			$table->foreignUlid('item_id')->nullable()->constrained('items');
			$table->decimal('amount', 10, 2);
			$table->string('payment_channel');
			$table->string('status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
