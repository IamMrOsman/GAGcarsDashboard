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
        if ($driver === 'mysql') {
            $hasPrimary = DB::selectOne("
                SELECT 1 FROM information_schema.statistics
                WHERE table_schema = DATABASE() AND table_name = 'transactions' AND index_name = 'PRIMARY'
                LIMIT 1
            ");
            if ($hasPrimary) {
                DB::statement('ALTER TABLE transactions DROP PRIMARY KEY, CHANGE ulid id CHAR(26) NOT NULL, ADD PRIMARY KEY (id)');
            } else {
                DB::statement('ALTER TABLE transactions CHANGE ulid id CHAR(26) NOT NULL, ADD PRIMARY KEY (id)');
            }
        } else {
            Schema::table('transactions', function (Blueprint $table) {
                $table->dropPrimary(['ulid']);
                $table->renameColumn('ulid', 'id');
                $table->primary('id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE transactions DROP PRIMARY KEY, CHANGE id ulid CHAR(26) NOT NULL, ADD PRIMARY KEY (ulid)');
        } else {
            Schema::table('transactions', function (Blueprint $table) {
                $table->dropPrimary(['id']);
                $table->renameColumn('id', 'ulid');
                $table->primary('ulid');
            });
        }
    }
};
