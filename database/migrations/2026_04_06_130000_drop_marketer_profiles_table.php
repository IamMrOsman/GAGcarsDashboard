<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('marketer_profiles');
    }

    public function down(): void
    {
        // Marketer profile feature removed; restore from backup / old migration if needed.
    }
};
