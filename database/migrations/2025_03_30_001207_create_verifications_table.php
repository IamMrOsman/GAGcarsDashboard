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
        Schema::create('verifications', function (Blueprint $table) {
            $table->id();
			$table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
			$table->string('document_type')->nullable();
			$table->string('document_number')->nullable();
			$table->string('document_front')->nullable();
			$table->string('document_back')->nullable();
			$table->enum('verification_type', ['dealer', 'individual'])->default('individual');
			$table->string('selfie')->nullable();
			$table->string('status')->default('pending');
			$table->text('comment')->nullable();
			$table->foreignUlid('verified_by')->nullable()->constrained('users')->nullOnDelete();
			$table->foreignUlid('rejected_by')->nullable()->constrained('users')->nullOnDelete();
			$table->dateTime('approved_at')->nullable();
			$table->dateTime('rejected_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('verifications');
    }
};
