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
        Schema::table('packages', function (Blueprint $table) {
            $table->string('package_type')->enum('upload', 'promotion');
            $table->integer('promotion_days')->nullable();
            $table->integer('number_of_listings')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn('package_type');
            $table->dropColumn('promotion_days');
            $table->integer('number_of_listings')->nullable(false)->change();
        });
    }
};
