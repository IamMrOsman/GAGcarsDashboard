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
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
			$table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
			$table->foreignUlid('item_id')->constrained()->cascadeOnDelete();
			$table->dateTime('start_at')->nullable();
			$table->dateTime('end_at')->nullable();
			$table->string('status')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};
