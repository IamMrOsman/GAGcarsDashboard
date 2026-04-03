<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class MarketerRoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::query()->firstOrCreate(
            ['name' => 'marketer', 'guard_name' => 'web'],
            ['name' => 'marketer', 'guard_name' => 'web'],
        );
    }
}
