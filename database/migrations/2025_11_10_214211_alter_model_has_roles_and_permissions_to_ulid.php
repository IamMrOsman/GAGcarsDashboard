<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');
        $modelMorphKey = $columnNames['model_morph_key'] ?? 'model_id';
        $pivotRole = $columnNames['role_pivot_key'] ?? 'role_id';
        $pivotPermission = $columnNames['permission_pivot_key'] ?? 'permission_id';
        $teams = config('permission.teams', false);

        // Alter model_has_roles table
        if (Schema::hasTable($tableNames['model_has_roles'])) {
            // Check if column is already ULID type
            $columnType = DB::select("SHOW COLUMNS FROM `{$tableNames['model_has_roles']}` WHERE Field = '{$modelMorphKey}'");

            if (!empty($columnType) && stripos($columnType[0]->Type, 'char') === false) {
                // Drop primary key if it exists
                try {
                    DB::statement("ALTER TABLE `{$tableNames['model_has_roles']}` DROP PRIMARY KEY");
                } catch (\Exception $e) {
                    // Primary key might not exist or have different name
                }

                // Drop index if it exists
                try {
                    DB::statement("ALTER TABLE `{$tableNames['model_has_roles']}` DROP INDEX `model_has_roles_model_id_model_type_index`");
                } catch (\Exception $e) {
                    // Index might not exist
                }

                // Alter column to ULID
                DB::statement("ALTER TABLE `{$tableNames['model_has_roles']}` MODIFY COLUMN `{$modelMorphKey}` CHAR(26) NOT NULL");

                // Recreate index
                DB::statement("ALTER TABLE `{$tableNames['model_has_roles']}` ADD INDEX `model_has_roles_model_id_model_type_index` (`{$modelMorphKey}`, `model_type`)");

                // Recreate primary key
                if ($teams) {
                    $teamForeignKey = $columnNames['team_foreign_key'] ?? 'team_id';
                    DB::statement("ALTER TABLE `{$tableNames['model_has_roles']}` ADD PRIMARY KEY (`{$teamForeignKey}`, `{$pivotRole}`, `{$modelMorphKey}`, `model_type`)");
                } else {
                    DB::statement("ALTER TABLE `{$tableNames['model_has_roles']}` ADD PRIMARY KEY (`{$pivotRole}`, `{$modelMorphKey}`, `model_type`)");
                }
            }
        }

        // Alter model_has_permissions table
        if (Schema::hasTable($tableNames['model_has_permissions'])) {
            // Check if column is already ULID type
            $columnType = DB::select("SHOW COLUMNS FROM `{$tableNames['model_has_permissions']}` WHERE Field = '{$modelMorphKey}'");

            if (!empty($columnType) && stripos($columnType[0]->Type, 'char') === false) {
                // Drop primary key if it exists
                try {
                    DB::statement("ALTER TABLE `{$tableNames['model_has_permissions']}` DROP PRIMARY KEY");
                } catch (\Exception $e) {
                    // Primary key might not exist or have different name
                }

                // Drop index if it exists
                try {
                    DB::statement("ALTER TABLE `{$tableNames['model_has_permissions']}` DROP INDEX `model_has_permissions_model_id_model_type_index`");
                } catch (\Exception $e) {
                    // Index might not exist
                }

                // Alter column to ULID
                DB::statement("ALTER TABLE `{$tableNames['model_has_permissions']}` MODIFY COLUMN `{$modelMorphKey}` CHAR(26) NOT NULL");

                // Recreate index
                DB::statement("ALTER TABLE `{$tableNames['model_has_permissions']}` ADD INDEX `model_has_permissions_model_id_model_type_index` (`{$modelMorphKey}`, `model_type`)");

                // Recreate primary key
                if ($teams) {
                    $teamForeignKey = $columnNames['team_foreign_key'] ?? 'team_id';
                    DB::statement("ALTER TABLE `{$tableNames['model_has_permissions']}` ADD PRIMARY KEY (`{$teamForeignKey}`, `{$pivotPermission}`, `{$modelMorphKey}`, `model_type`)");
                } else {
                    DB::statement("ALTER TABLE `{$tableNames['model_has_permissions']}` ADD PRIMARY KEY (`{$pivotPermission}`, `{$modelMorphKey}`, `model_type`)");
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration is not easily reversible without data loss
        // If you need to reverse, you would need to convert ULIDs back to integers
        // which is not recommended
    }
};
