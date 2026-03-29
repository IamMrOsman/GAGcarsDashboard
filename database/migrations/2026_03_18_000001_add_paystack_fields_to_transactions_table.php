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
		Schema::table('transactions', function (Blueprint $table) {
			$table->string('reference')->nullable()->unique()->after('status');
			$table->string('gateway')->nullable()->after('reference');
			$table->string('gateway_transaction_id')->nullable()->after('gateway');
			$table->string('currency', 10)->nullable()->after('gateway_transaction_id');
			$table->dateTime('paid_at')->nullable()->after('currency');
			$table->dateTime('fulfilled_at')->nullable()->after('paid_at');
			$table->json('metadata')->nullable()->after('fulfilled_at');
			$table->json('gateway_response')->nullable()->after('metadata');
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::table('transactions', function (Blueprint $table) {
			$table->dropUnique(['reference']);
			$table->dropColumn([
				'reference',
				'gateway',
				'gateway_transaction_id',
				'currency',
				'paid_at',
				'fulfilled_at',
				'metadata',
				'gateway_response',
			]);
		});
	}
};

