<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::create('deleted_user_archives', function (Blueprint $table) {
			$table->ulid('id')->primary();
			$table->foreignUlid('original_user_id')->constrained('users');
			$table->json('payload');
			$table->timestamp('archived_at')->useCurrent();
			$table->timestamps();

			$table->index(['original_user_id']);
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('deleted_user_archives');
	}
};

