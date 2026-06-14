<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cache Spatie
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // ─── 1. Créer le rôle superadmin ─────────────────────────
        $role = Role::firstOrCreate([
            'name'       => 'superadmin',
            'guard_name' => 'api',
        ]);

        // ─── 2. Lui donner TOUTES les permissions ─────────────────
        $permissions = Permission::where('guard_name', 'api')->get();
        $role->syncPermissions($permissions);

        $this->command->info("✅ Rôle superadmin créé avec {$permissions->count()} permissions");

        // ─── 3. Créer l'utilisateur superadmin ───────────────────
        $user = User::firstOrCreate(
            ['email' => 'superadmin@fasolivestock.bf'],
            [
                'name'         => 'Super Administrateur',
                'password'     => Hash::make('SuperAdmin@2026!'),
                'telephone'    => '+22600000000',
                'is_active'    => true,
                'last_sync_at' => now(),
            ]
        );

        // ─── 4. Assigner le rôle superadmin ──────────────────────
        if (!$user->hasRole('superadmin')) {
            $user->assignRole($role);
            $this->command->info("✅ Utilisateur superadmin créé");
        } else {
            $this->command->info("⏭️  Utilisateur superadmin déjà existant");
        }

        $this->command->newLine();
        $this->command->warn("⚠️  IMPORTANT : Changez le mot de passe superadmin en production !");
        $this->command->info("📧 Email    : superadmin@fasolivestock.bf");
        $this->command->info("🔑 Password : SuperAdmin@2026!");
    }
}