<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::create('user_notifications', function (Blueprint $table) {
			$table->bigIncrements('id');
			$table->foreignUlid('user_id')->constrained('users')->cascadeOnDelete();

			$table->string('title');
			$table->text('message');
			$table->string('notification_type', 64); // chat_message | blog_post | broadcast | ...
			$table->boolean('is_read')->default(false);
			$table->json('data')->nullable(); // deep_link, ids, etc.

			$table->timestamps();

			$table->index(['user_id', 'is_read']);
			$table->index(['user_id', 'notification_type']);
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('user_notifications');
	}
};

