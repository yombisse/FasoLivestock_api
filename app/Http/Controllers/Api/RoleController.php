<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Role\StoreRoleRequest;
use App\Http\Requests\Role\UpdateRoleRequest;
use App\Helpers\ApiResponse;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    /**
     * Lister les utilisateurs assignés à un rôle.
     */
    public function users(string $id)
    {
        $role = $this->baseQuery()->find($id);

        if (!$role) {
            return ApiResponse::error('Rôle introuvable', null, 404);
        }

        // Spatie: relation Role->users
        $users = $role->users()->get()->map(function (User $user) {
            return [
                'id'       => $user->id,
                'name'     => $user->name,
                'email'    => $user->email,
                'telephone'=> $user->telephone,
                'roles'    => $user->getRoleNames()->values(),
            ];
        });

        return ApiResponse::success(
            [
                'users' => $users,
                'count' => $users->count(),
            ],
            'Utilisateurs récupérés avec succès.'
        );
    }

    /**
     * Assigner un utilisateur à un rôle (Spatie uniquement).
     * body: { "user_id": "..." }
     */
    public function attachUser(Request $request, string $id)
    {
        $role = $this->baseQuery()->find($id);

        if (!$role) {
            return ApiResponse::error('Rôle introuvable', null, 404);
        }

        if ($this->isSuperAdmin($role)) {
            return ApiResponse::error(
                'Le rôle superadmin ne peut pas être modifié.',
                null,
                403
            );
        }

        $userId = $request->input('user_id');

        if (!$userId) {
            return ApiResponse::error('user_id est requis', null, 422);
        }

        $user = User::find($userId);

        if (!$user) {
            return ApiResponse::error('Utilisateur introuvable', null, 404);
        }

        // Spatie: assignRole respecte le guard_name du role via role->guard_name
        $user->assignRole($role);

        return ApiResponse::success(
            null,
            'Utilisateur assigné au rôle avec succès.',
            201
        );
    }

    /**
     * Retirer un utilisateur d’un rôle (Spatie uniquement).
     */
    public function detachUser(string $id, string $userId)
    {
        $role = $this->baseQuery()->find($id);

        if (!$role) {
            return ApiResponse::error('Rôle introuvable', null, 404);
        }

        if ($this->isSuperAdmin($role)) {
            return ApiResponse::error(
                'Le rôle superadmin ne peut pas être modifié.',
                null,
                403
            );
        }

        $user = User::find($userId);

        if (!$user) {
            return ApiResponse::error('Utilisateur introuvable', null, 404);
        }

        $user->removeRole($role);

        return ApiResponse::success(
            null,
            'Utilisateur retiré du rôle avec succès.'
        );
    }

    /**
     * Lister tous les rôles avec leurs permissions et le nombre d'utilisateurs.
     */
    public function index()
    {
        $roles = $this->baseQuery()
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

        $role->syncPermissions($request->permissions ?? []);

        // load('permissions')/loadCount('users') dépend de la configuration Spatie (relation users_count)
        return ApiResponse::success(
            $this->formatRole($role->load('permissions')->loadCount('users')),
            'Rôle créé avec succès.',
            201
        );
    }

    /**
     * Détail d'un rôle.
     */
    public function show(string $id)
    {
            $role = $this->baseQuery()
            ->find($id);


        if (!$role) {
            return ApiResponse::error('Rôle introuvable', null, 404);
        }

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
        $role = $this->baseQuery()->find($id);


        if (!$role) {
            return ApiResponse::error('Rôle introuvable', null, 404);
        }

        if ($this->isSuperAdmin($role)) {
            return ApiResponse::error(
                'Le rôle superadmin ne peut pas être modifié.',
                null,
                403
            );
        }

        if ($request->filled('name')) {
            $role->update(['name' => $request->name]);
        }

        if ($request->filled('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        return ApiResponse::success(
            $this->formatRole($role->load('permissions')),
            'Rôle mis à jour avec succès.'
        );
    }

    /**
     * Supprimer un rôle.
     * ATTENTION : Spatie Role n'implémente pas soft delete par défaut.
     */
    public function destroy(string $id)
    {
        $role = Role::where('guard_name', 'api')
            ->find($id);


        if (!$role) {
            return ApiResponse::error('Rôle introuvable', null, 404);
        }

        if ($this->isSuperAdmin($role)) {
            return ApiResponse::error(
                'Le rôle superadmin ne peut pas être supprimé.',
                null,
                403
            );
        }

        if (($role->users_count ?? 0) > 0) {
            return ApiResponse::error(
                "Ce rôle est assigné à {$role->users_count} utilisateur(s).",
                null,
                422
            );
        }

        $role->delete();

        return ApiResponse::success(null, 'Rôle supprimé avec succès.');
    }

    /**
     * Retourner toutes les permissions groupées par module.
     * Utilisé par le frontend pour afficher les cases à cocher.
     */
    public function permissions(Request $request = null)
    {
        return ApiResponse::success(
            $this->getPermissionsGrouped(),
            'Permissions récupérées avec succès.'
        );
    }

    private function baseQuery()
    {
        // Important : on filtre guard_name='api' (cohérent avec tes services/assignRole)
        return Role::where('guard_name', 'api')->with('permissions');
    }

    private function isSuperAdmin(Role $role): bool
    {
        return $role->name === 'superadmin';
    }

    /**
     * Formater un rôle pour la réponse API.
     */
    private function formatRole(Role $role): array
    {
        return [
            'id'          => $role->id,
            'name'        => $role->name,
            'is_system'   => $this->isSuperAdmin($role),
            'permissions' => $role->permissions->pluck('name')->sort()->values(),
            'users_count' => $role->users_count ?? $role->users()->count(),
            // Spatie Role peut ne pas avoir deleted_at si soft delete non activé.
            'deleted_at'  => $role->deleted_at ?? null,
            'created_at'  => $role->created_at,
        ];
    }

    /**
     * Retourne les permissions groupées par module.
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

    /**
     * Lister les rôles archivés.
     * Note: si soft delete Spatie est désactivé, cet endpoint n'est pas supporté.
     */
    public function trashed()
    {
        return ApiResponse::error(
            'Soft delete non supporté pour Spatie\Permission\Models\Role (à activer si besoin).',
            null,
            400
        );
    }

    /**
     * Restaurer un rôle archivé.
     */
    public function restore(string $id)
    {
        return ApiResponse::error(
            'Soft delete non supporté pour Spatie\Permission\Models\Role (à activer si besoin).',
            null,
            400
        );
    }
}

