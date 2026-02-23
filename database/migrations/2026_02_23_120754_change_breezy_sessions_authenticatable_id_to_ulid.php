<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	/**
	 * Run the migrations.
	 */
	public function up(): void
	{
		$driver = Schema::getConnection()->getDriverName();
		$indexName = 'breezy_sessions_authenticatable_type_authenticatable_id_index';

		if ($driver === 'mysql') {
			DB::statement("ALTER TABLE breezy_sessions DROP INDEX {$indexName}");
			DB::statement('ALTER TABLE breezy_sessions MODIFY authenticatable_id CHAR(26) NOT NULL');
			DB::statement("ALTER TABLE breezy_sessions ADD INDEX {$indexName} (authenticatable_type, authenticatable_id)");
		} else {
			Schema::table('breezy_sessions', function (Blueprint $table) {
				$table->dropIndex($indexName);
				$table->char('authenticatable_id', 26)->change();
				$table->index(['authenticatable_type', 'authenticatable_id'], $indexName);
			});
		}
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		$driver = Schema::getConnection()->getDriverName();
		$indexName = 'breezy_sessions_authenticatable_type_authenticatable_id_index';

		if ($driver === 'mysql') {
			DB::statement("ALTER TABLE breezy_sessions DROP INDEX {$indexName}");
			DB::statement('ALTER TABLE breezy_sessions MODIFY authenticatable_id BIGINT UNSIGNED NOT NULL');
			DB::statement("ALTER TABLE breezy_sessions ADD INDEX {$indexName} (authenticatable_type, authenticatable_id)");
		} else {
			Schema::table('breezy_sessions', function (Blueprint $table) {
				$table->dropIndex($indexName);
				$table->unsignedBigInteger('authenticatable_id')->change();
				$table->index(['authenticatable_type', 'authenticatable_id'], $indexName);
			});
		}
	}
};
