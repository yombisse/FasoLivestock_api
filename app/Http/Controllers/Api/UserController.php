<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Helpers\ApiResponse;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    // =========================================================
    // MÉTHODES PUBLIQUES
    // =========================================================

    /**
     * Lister tous les utilisateurs actifs avec pagination et filtres.
     */
    public function index(Request $request)
    {
        $users = User::query()
            ->with('roles.permissions')
            ->withCount('farms')
            ->when($request->search, fn ($q) =>
                $q->where(fn ($q) =>
                    $q->where('name', 'like', "%{$request->search}%")
                      ->orWhere('email', 'like', "%{$request->search}%")
                      ->orWhere('telephone', 'like', "%{$request->search}%")
                )
            )
            ->when($request->role, fn ($q) =>
                $q->whereHas('roles', fn ($r) =>
                    $r->where('name', $request->role)
                      ->where('guard_name', 'api')
                )
            )
            ->when($request->has('is_active'), fn ($q) =>
                $q->where('is_active', $request->boolean('is_active'))
            )
            ->orderBy('name')
            ->paginate($request->per_page ?? 15);

        return ApiResponse::success([
            'users' => $users->map(fn ($user) => $this->formatUser($user)),
            'meta'  => [
                'total'        => $users->total(),
                'per_page'     => $users->perPage(),
                'current_page' => $users->currentPage(),
                'last_page'    => $users->lastPage(),
            ],
        ], 'Utilisateurs récupérés avec succès.');
    }

    /**
     * Créer un utilisateur et lui assigner des rôles.
     */
    public function store(StoreUserRequest $request)
    {
        $user = User::create([
            'name'         => $request->name,
            'email'        => $request->email,
            'telephone'    => $request->telephone,
            'photo'        => $request->photo,
            'password'     => Hash::make($request->password),
            'is_active'    => true,
            'last_sync_at' => now(),
        ]);

        $user->assignRole($this->resolveRoles($request->roles));

        return ApiResponse::success(
            $this->formatUser($user->loadCount('farms')->load('roles.permissions')),
            'Utilisateur créé avec succès.',
            201
        );
    }

    /**
     * Détail d'un utilisateur — Route Model Binding.
     */
    public function show(User $user)
    {
        $user->load('roles.permissions', 'farms')->loadCount('farms');

        return ApiResponse::success(
            $this->formatUser($user),
            'Utilisateur récupéré avec succès.'
        );
    }

    /**
     * Modifier un utilisateur — Route Model Binding.
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        $this->authorize('update', $user);

        $data = $request->only(['name', 'email', 'telephone', 'photo', 'is_active']);

        if ($request->has('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        if ($request->has('roles')) {
            $user->syncRoles($this->resolveRoles($request->roles));
        }

        return ApiResponse::success(
            $this->formatUser($user->loadCount('farms')->load('roles.permissions')),
            'Utilisateur mis à jour avec succès.'
        );
    }

    /**
     * Activer / Désactiver un utilisateur — Route Model Binding.
     */
    public function toggleActive(User $user)
    {
        $this->authorize('toggleActive', $user);

        $user->update(['is_active' => !$user->is_active]);
        $status = $user->is_active ? 'activé' : 'désactivé';

        return ApiResponse::success(
            $this->formatUser($user->load('roles')),
            "Utilisateur {$status} avec succès."
        );
    }

    /**
     * Archiver un utilisateur — soft delete — Route Model Binding.
     */
    public function destroy(User $user)
    {
        $this->authorize('delete', $user);

        $user->update(['is_active' => false]);
        $user->delete();

        return ApiResponse::success(null, 'Utilisateur archivé avec succès.');
    }

    /**
     * Lister les utilisateurs archivés.
     */
    public function trashed(Request $request)
    {
        $users = User::onlyTrashed()
            ->with('roles.permissions')
            ->withCount('farms')
            ->when($request->search, fn ($q) =>
                $q->where(fn ($q) =>
                    $q->where('name', 'like', "%{$request->search}%")
                      ->orWhere('email', 'like', "%{$request->search}%")
                )
            )
            ->orderBy('deleted_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return ApiResponse::success([
            'users' => $users->map(fn ($user) => $this->formatUser($user)),
            'meta'  => [
                'total'        => $users->total(),
                'per_page'     => $users->perPage(),
                'current_page' => $users->currentPage(),
                'last_page'    => $users->lastPage(),
            ],
        ], 'Utilisateurs archivés récupérés avec succès.');
    }

    /**
     * Restaurer un utilisateur archivé.
     */
    public function restore(string $id)
    {
        // onlyTrashed incompatible avec Route Model Binding standard
        // donc on garde string $id ici
        $user = User::onlyTrashed()->findOrFail($id);

        $user->restore();
        $user->update(['is_active' => true]);

        return ApiResponse::success(
            $this->formatUser($user->loadCount('farms')->load('roles.permissions')),
            'Utilisateur restauré avec succès.'
        );
    }

    // =========================================================
    // MÉTHODES PRIVÉES
    // =========================================================

    /**
     * Résoudre les rôles depuis leurs noms en objets Eloquent.
     * Garantit le respect du guard api.
     */
    private function resolveRoles(array $roleNames)
    {
        return Role::where('guard_name', 'api')
            ->whereIn('name', $roleNames)
            ->get();
    }

    /**
     * Formater un utilisateur pour la réponse API.
     * Utilise farms_count si déjà chargé via withCount.
     */
    private function formatUser(User $user): array
    {
        // Défensif : éviter des 500 si les relations roles/permissions ne sont pas
        // (ou partiellement) chargées.
        $roles = $user->relationLoaded('roles')
            ? ($user->roles ?? collect())
            : ($user->roles ?? collect());

        return [
            'id'           => $user->id,
            'name'         => $user->name,
            'email'        => $user->email,
            'telephone'    => $user->telephone,
            'photo'        => $user->photo,
            'is_active'    => $user->is_active,

            'roles' => $roles->map(fn ($r) => [
                'id'   => $r->id,
                'name' => $r->name,

                'permissions' => (isset($r->permissions) ? $r->permissions : collect())
                    ->pluck('name')
                    ->sort()
                    ->values(),
            ])->values(),

            'farms_count'  => $user->farms_count ?? $user->farms()->count(),
            'last_sync_at' => $user->last_sync_at,
            'deleted_at'   => $user->deleted_at,
            'created_at'   => $user->created_at,
        ];
    }
}