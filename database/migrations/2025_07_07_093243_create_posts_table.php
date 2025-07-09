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
        Schema::create('posts', function (Blueprint $table) {
            $table->ulid('id')->primary();
			$table->foreignUlid('user_id')->constrained('users');
			$table->foreignUlid('country_id')->constrained('countries');
			$table->string('title');
			$table->string('slug');
			$table->text('description')->nullable();
			$table->string('image')->nullable();
			$table->string('status');
			$table->string('content')->nullable();
			$table->json('tags')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
