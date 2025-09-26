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
        Schema::table('verifications', function (Blueprint $table) {
			$table->string('dealership_name')->nullable();
			$table->string('address')->nullable();
			$table->string('dealership_registration_document')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('verifications', function (Blueprint $table) {
            $table->dropColumn('dealership_name');
            $table->dropColumn('address');
            $table->dropColumn('dealership_registration_document');
        });
    }
};
