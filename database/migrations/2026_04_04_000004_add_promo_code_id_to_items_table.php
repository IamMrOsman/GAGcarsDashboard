<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('items')) {
            return;
        }

        Schema::table('items', function (Blueprint $table) {
            if (! Schema::hasColumn('items', 'promo_code_id')) {
                $table->foreignUlid('promo_code_id')->nullable()->after('user_id')->constrained('promo_codes')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('items')) {
            return;
        }

        Schema::table('items', function (Blueprint $table) {
            if (Schema::hasColumn('items', 'promo_code_id')) {
                $table->dropConstrainedForeignId('promo_code_id');
            }
        });
    }
};
