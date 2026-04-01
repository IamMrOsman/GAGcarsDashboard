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
		Schema::create('device_tokens', function (Blueprint $table) {
			$table->bigIncrements('id');
			$table->foreignUlid('user_id')->constrained('users')->cascadeOnDelete();

			// FCM token can be > 255 chars; don't index raw token (MySQL index limits).
			$table->text('token');
			$table->char('token_hash', 64)->unique();

			$table->string('platform', 32)->nullable(); // android | ios | web
			$table->string('device_id', 128)->nullable(); // optional client identifier
			$table->timestamp('last_used_at')->nullable();

			$table->timestamps();

			$table->index(['user_id', 'platform']);
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::dropIfExists('device_tokens');
	}
};

