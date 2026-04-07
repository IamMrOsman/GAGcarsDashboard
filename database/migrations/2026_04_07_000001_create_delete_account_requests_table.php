<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::create('delete_account_requests', function (Blueprint $table) {
			$table->ulid('id')->primary();
			$table->foreignUlid('user_id')->constrained('users');
			$table->string('status')->default('pending'); // pending|approved|rejected
			$table->json('snapshot')->nullable();
			$table->timestamp('requested_at')->useCurrent();
			$table->timestamp('reviewed_at')->nullable();
			$table->foreignUlid('reviewed_by')->nullable()->constrained('users');
			$table->text('reason')->nullable();
			$table->timestamps();

			$table->index(['user_id', 'status']);
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('delete_account_requests');
	}
};

