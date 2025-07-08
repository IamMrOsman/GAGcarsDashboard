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
        Schema::create('broadcasts', function (Blueprint $table) {
            $table->ulid('id')->primary();
			$table->foreignUlid('user_id')->constrained('users');
			$table->foreignId('country_id')->nullable()->constrained('countries');
			$table->string('subject');
			$table->text('message');
			$table->string('image')->nullable();
			$table->string('status')->default('draft');
			$table->dateTime('scheduled_at')->nullable();
			$table->enum('target', ['customers', 'dealers', 'all'])->default('customers');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('broadcasts');
    }
};
