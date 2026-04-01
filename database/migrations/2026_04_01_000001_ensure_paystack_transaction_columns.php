<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Remote DBs (e.g. test-gagcars-db) may be missing Paystack columns if
 * 2026_03_18_000001_add_paystack_fields_to_transactions_table was never applied.
 * This migration adds only columns that are absent (safe to re-run).
 */
return new class extends Migration
{
	public function up(): void
	{
		if (!Schema::hasTable('transactions')) {
			return;
		}

		if (!Schema::hasColumn('transactions', 'reference')) {
			Schema::table('transactions', function (Blueprint $table) {
				$table->string('reference')->nullable()->unique()->after('status');
			});
		}

		if (!Schema::hasColumn('transactions', 'gateway')) {
			Schema::table('transactions', function (Blueprint $table) {
				$table->string('gateway')->nullable()->after('reference');
			});
		}

		if (!Schema::hasColumn('transactions', 'gateway_transaction_id')) {
			Schema::table('transactions', function (Blueprint $table) {
				$table->string('gateway_transaction_id')->nullable()->after('gateway');
			});
		}

		if (!Schema::hasColumn('transactions', 'currency')) {
			Schema::table('transactions', function (Blueprint $table) {
				$table->string('currency', 10)->nullable()->after('gateway_transaction_id');
			});
		}

		if (!Schema::hasColumn('transactions', 'paid_at')) {
			Schema::table('transactions', function (Blueprint $table) {
				$table->dateTime('paid_at')->nullable()->after('currency');
			});
		}

		if (!Schema::hasColumn('transactions', 'fulfilled_at')) {
			Schema::table('transactions', function (Blueprint $table) {
				$table->dateTime('fulfilled_at')->nullable()->after('paid_at');
			});
		}

		if (!Schema::hasColumn('transactions', 'metadata')) {
			Schema::table('transactions', function (Blueprint $table) {
				$table->json('metadata')->nullable()->after('fulfilled_at');
			});
		}

		if (!Schema::hasColumn('transactions', 'gateway_response')) {
			Schema::table('transactions', function (Blueprint $table) {
				$table->json('gateway_response')->nullable()->after('metadata');
			});
		}
	}

	public function down(): void
	{
		// Intentionally empty: do not strip Paystack columns from production.
	}
};
