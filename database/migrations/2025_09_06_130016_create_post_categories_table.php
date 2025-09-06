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
        Schema::create('post_categories', function (Blueprint $table) {
            $table->id();
			$table->foreignUlid('user_id')->nullable()->constrained('users')->nullOnDelete();
			$table->foreignId('parent_id')->nullable()->constrained('post_categories')->onDelete('cascade');
			$table->string('name')->unique();
			$table->string('slug')->unique();
			$table->string('description')->nullable();
			$table->string('image')->nullable();
			$table->string('status')->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('post_categories');
    }
};
