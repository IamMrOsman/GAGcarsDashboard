<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('items')) {
            return;
        }

        Schema::table('items', function (Blueprint $table) {
            if (!Schema::hasColumn('items', 'draft_payload')) {
                $table->json('draft_payload')->nullable()->after('features');
            }
            if (!Schema::hasColumn('items', 'draft_step')) {
                $table->string('draft_step')->nullable()->after('draft_payload');
            }
            if (!Schema::hasColumn('items', 'last_saved_at')) {
                $table->dateTime('last_saved_at')->nullable()->after('draft_step');
            }
            if (!Schema::hasColumn('items', 'expires_at')) {
                $table->dateTime('expires_at')->nullable()->after('last_saved_at');
            }
        });
    }

    public function down(): void
    {
        // Intentionally empty: do not drop draft columns in production.
    }
};

