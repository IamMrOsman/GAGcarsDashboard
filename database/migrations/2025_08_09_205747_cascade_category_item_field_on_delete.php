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
        Schema::table('category_item_field', function (Blueprint $table) {
			$table->dropForeign(['category_id']);
			$table->dropForeign(['item_field_id']);
			$table->foreign('category_id')->references('id')->on('categories')->cascadeOnDelete();
			$table->foreign('item_field_id')->references('id')->on('item_fields')->cascadeOnDelete();
		});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
