<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('countries')) {
            return;
        }

        DB::statement("ALTER TABLE `countries` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        DB::statement("ALTER TABLE `countries` MODIFY `emoji` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL");
    }

    public function down(): void
    {
        // Intentionally not converting back (risk of data loss for 4-byte chars).
    }
};

