<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promo_codes', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('code', 64);
            $table->foreignUlid('marketer_id')->constrained('users')->cascadeOnDelete();
            $table->string('discount_type', 16)->comment('percent|fixed');
            $table->decimal('discount_value', 12, 2);
            $table->timestamp('expires_at')->nullable();
            $table->unsignedInteger('max_uses')->nullable();
            $table->unsignedInteger('uses_count')->default(0);
            $table->boolean('active')->default(true);
            $table->foreignUlid('package_id')->nullable()->constrained('packages')->nullOnDelete();
            $table->timestamps();

            $table->unique('code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promo_codes');
    }
};
