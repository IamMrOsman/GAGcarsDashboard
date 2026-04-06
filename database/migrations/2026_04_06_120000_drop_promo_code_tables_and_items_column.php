<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('promo_redemptions');

        if (Schema::hasTable('items') && Schema::hasColumn('items', 'promo_code_id')) {
            Schema::table('items', function (Blueprint $table) {
                $table->dropConstrainedForeignId('promo_code_id');
            });
        }

        Schema::dropIfExists('promo_codes');
    }

    public function down(): void
    {
        // Promo feature removed; re-run original create migrations from backup if you need to restore.
    }
};
