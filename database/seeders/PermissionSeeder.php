<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    /**
     * Permissions groupées par module
     * Format généré : module.action → ex: animals.view
     */
    private array $permissions = [
        // ─── Utilisateurs ────────────────────────────────────────
        'users' => ['view', 'create', 'update', 'delete'],

        // ─── Fermes ──────────────────────────────────────────────
        'farms' => ['view', 'create', 'update', 'delete'],

        // ─── Animaux ─────────────────────────────────────────────
        'animals' => ['view', 'create', 'update', 'delete'],

        // ─── Lots ────────────────────────────────────────────────
        'lots' => ['view', 'create', 'update', 'delete'],

        // ─── Événements ──────────────────────────────────────────
        'evenements' => ['view', 'create', 'update', 'delete'],

        // ─── Naissances ──────────────────────────────────────────
        'naissances' => ['view', 'create', 'update', 'delete'],

        // ─── Santé / Rappels ─────────────────────────────────────
        'sante_rappels' => ['view', 'create', 'update', 'delete'],

        // ─── Alimentation ────────────────────────────────────────
        'aliments' => ['view', 'create', 'update', 'delete'],
        'rations'  => ['view', 'create', 'update', 'delete'],

        // ─── Transactions ────────────────────────────────────────
        'transactions' => ['view', 'create', 'update', 'delete'],

        // ─── Notifications ───────────────────────────────────────
        'notifications' => ['view'],

        // ─── Rapports ────────────────────────────────────────────
        'rapports' => ['view', 'export'],

        // ─── Dashboard ───────────────────────────────────────────
        'dashboard' => ['view'],

        // ─── Rôles (gestion dynamique) ───────────────────────────
        'roles' => ['view', 'create', 'update', 'delete'],

        // ─── Espèces & Paramètres (admin uniquement) ─────────────
        'especes'           => ['view', 'create', 'update', 'delete'],
        'espece_parametres' => ['view', 'create', 'update', 'delete'],
    ];

    public function run(): void
    {
        // Reset du cache Spatie avant de seeder
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $created = 0;
        $skipped = 0;

        foreach ($this->permissions as $module => $actions) {
            foreach ($actions as $action) {
                $name = "{$module}.{$action}";

                $permission = Permission::firstOrCreate([
                    'name'       => $name,
                    'guard_name' => 'api',
                ]);

                $permission->wasRecentlyCreated ? $created++ : $skipped++;
            }
        }

        $this->command->info("✅ Permissions créées  : {$created}");
        $this->command->info("⏭️  Permissions existantes : {$skipped}");
        $this->command->info("📋 Total : " . ($created + $skipped) . " permissions");
    }

    /**
     * Retourne toutes les permissions groupées par module
     * Utilisé par le RoleController pour l'affichage frontend
     */
    public static function getGrouped(): array
    {
        return Permission::where('guard_name', 'api')
            ->get()
            ->groupBy(fn ($p) => explode('.', $p->name)[0])
            ->map(fn ($group) => $group->map(fn ($p) => [
                'id'     => $p->id,
                'name'   => $p->name,
                'action' => explode('.', $p->name)[1],
            ]))
            ->toArray();
    }
}