<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Role\StoreRoleRequest;
use App\Http\Requests\Role\UpdateRoleRequest;
use App\Helpers\ApiResponse;
use App\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    // =========================================================
    // MÉTHODES PUBLIQUES
    // =========================================================

    /**
     * Lister tous les rôles avec leurs permissions et le nombre d'utilisateurs.
     * Utilise withCount pour éviter le N+1 sur users_count.
     */
    public function index()
    {
        $roles = $this->baseQuery()
            ->withCount('users')
            ->get()
            ->map(fn ($role) => $this->formatRole($role));

        return ApiResponse::success([
            'roles'       => $roles,
            'permissions' => $this->getPermissionsGrouped(),
        ], 'Rôles récupérés avec succès.');
    }

    /**
     * Créer un rôle avec ses permissions.
     */
    public function store(StoreRoleRequest $request)
    {
        $role = Role::create([
            'name'       => $request->name,
            'guard_name' => 'api',
        ]);

        $role->syncPermissions($request->permissions);

        return ApiResponse::success(
            $this->formatRole($role->loadCount('users')->load('permissions')),
            'Rôle créé avec succès.',
            201
        );
    }

    /**
     * Détail d'un rôle avec ses permissions et le nombre d'utilisateurs.
     */
    public function show(string $id)
    {
        $role = $this->baseQuery()
            ->withCount('users')
            ->findOrFail($id);

        return ApiResponse::success(
            $this->formatRole($role),
            'Rôle récupéré avec succès.'
        );
    }

    /**
     * Modifier un rôle et/ou ses permissions.
     * Le rôle superadmin est protégé contre toute modification.
     */
    public function update(UpdateRoleRequest $request, string $id)
    {
        $role = $this->baseQuery()->withCount('users')->findOrFail($id);

        if ($this->isSuperAdmin($role)) {
            return ApiResponse::error(
                'Le rôle superadmin ne peut pas être modifié.',
                null,
                403
            );
        }

        if ($request->has('name')) {
            $role->update(['name' => $request->name]);
        }

        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        return ApiResponse::success(
            $this->formatRole($role->load('permissions')),
            'Rôle mis à jour avec succès.'
        );
    }

    /**
     * Archiver un rôle (soft delete).
     * Bloqué si des utilisateurs sont encore assignés à ce rôle.
     */
    public function destroy(string $id)
    {
        $role = $this->baseQuery()->withCount('users')->findOrFail($id);

        if ($this->isSuperAdmin($role)) {
            return ApiResponse::error(
                'Le rôle superadmin ne peut pas être supprimé.',
                null,
                403
            );
        }

        if ($role->users_count > 0) {
            return ApiResponse::error(
                "Ce rôle est assigné à {$role->users_count} utilisateur(s). Réassignez-les avant de supprimer.",
                null,
                422
            );
        }

        $role->delete();

        return ApiResponse::success(null, 'Rôle archivé avec succès.');
    }

    /**
     * Restaurer un rôle archivé.
     */
    public function restore(string $id)
    {
        $role = Role::where('guard_name', 'api')
            ->onlyTrashed()
            ->withCount('users')
            ->with('permissions')
            ->findOrFail($id);

        $role->restore();

        return ApiResponse::success(
            $this->formatRole($role),
            'Rôle restauré avec succès.'
        );
    }

    /**
     * Lister les rôles archivés.
     */
    public function trashed()
    {
        $roles = Role::where('guard_name', 'api')
            ->onlyTrashed()
            ->withCount('users')
            ->with('permissions')
            ->get()
            ->map(fn ($role) => $this->formatRole($role));

        return ApiResponse::success($roles, 'Rôles archivés récupérés.');
    }

    /**
     * Retourner toutes les permissions groupées par module.
     * Utilisé par le frontend pour afficher les cases à cocher.
     */
    public function permissions()
    {
        return ApiResponse::success(
            $this->getPermissionsGrouped(),
            'Permissions récupérées avec succès.'
        );
    }

    // =========================================================
    // MÉTHODES PRIVÉES
    // =========================================================

    /**
     * Query de base réutilisable — filtre sur guard api + charge permissions.
     */
    private function baseQuery()
    {
        return Role::where('guard_name', 'api')->with('permissions');
    }

    /**
     * Vérifie si le rôle est le superadmin système.
     */
    private function isSuperAdmin(Role $role): bool
    {
        return $role->name === 'superadmin';
    }

    /**
     * Formate un rôle pour la réponse API.
     * Utilise users_count si déjà chargé via withCount, sinon fallback.
     */
    private function formatRole(Role $role): array
    {
        return [
            'id'          => $role->id,
            'name'        => $role->name,
            'is_system'   => $this->isSuperAdmin($role),
            'permissions' => $role->permissions->pluck('name')->sort()->values(),
            'users_count' => $role->users_count ?? $role->users()->count(),
            'deleted_at'  => $role->deleted_at,
            'created_at'  => $role->created_at,
        ];
    }

    /**
     * Retourne les permissions groupées par module pour le frontend.
     * Sécurise explode() contre les permissions mal nommées.
     */
    private function getPermissionsGrouped(): array
    {
        return Permission::where('guard_name', 'api')
            ->orderBy('name')
            ->get()
            ->groupBy(function ($p) {
                $parts = explode('.', $p->name);
                return $parts[0] ?? 'autres';
            })
            ->map(fn ($group, $module) => [
                'module'      => $module,
                'permissions' => $group->map(fn ($p) => [
                    'id'     => $p->id,
                    'name'   => $p->name,
                    'action' => explode('.', $p->name)[1] ?? $p->name,
                ])->values(),
            ])
            ->values()
            ->toArray();
    }
}