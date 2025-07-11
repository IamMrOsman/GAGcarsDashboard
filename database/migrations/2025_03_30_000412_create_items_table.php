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
        Schema::create('items', function (Blueprint $table) {
            $table->ulid('id')->primary();
			$table->foreignUlid('user_id')->nullable()->constrained()->cascadeOnDelete();
			$table->foreignId('country_id')->nullable()->constrained()->nullOnDelete();
			$table->foreignId('brand_model_id')->nullable()->constrained()->nullOnDelete();
			$table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
			$table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
			$table->string('name');
			$table->year('year')->nullable();
			$table->string('slug')->unique();
			$table->string('description')->nullable();
			$table->json('images')->nullable();
			$table->string('location')->nullable();
			$table->string('serial_number')->nullable();
			$table->string('condition')->nullable();
			$table->string('steer_position')->nullable();
			$table->string('engine_capacity')->nullable();
			$table->string('transmission')->nullable();
			$table->json('color')->nullable();
			$table->string('build_type')->nullable();
			$table->integer('number_of_passengers')->nullable();
			$table->json('features')->nullable();
			$table->string('status')->nullable();
			$table->string('price')->nullable();
			$table->string('mileage')->nullable();
			$table->boolean('warranty')->nullable();
			$table->string('warranty_expiration')->nullable();
			$table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
