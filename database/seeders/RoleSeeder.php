<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::firstOrCreate([
            'name' => 'owner',
            'guard_name' => 'api',
        ]);

        Role::firstOrCreate([
            'name' => 'manager',
            'guard_name' => 'api',
        ]);

        Role::firstOrCreate([
            'name' => 'vet',
            'guard_name' => 'api',
        ]);

        Role::firstOrCreate([
            'name' => 'worker',
            'guard_name' => 'api',
        ]);
    }
}