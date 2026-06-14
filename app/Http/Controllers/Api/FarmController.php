<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Farm\StoreFarmRequest;
use App\Http\Requests\Farm\UpdateFarmRequest;
use App\Helpers\ApiResponse;
use App\Models\Farm;
use App\Models\User;
use Illuminate\Http\Request;

class FarmController extends Controller
{
    // =========================================================
    // MÉTHODES PUBLIQUES
    // =========================================================

    /**
     * Lister les fermes accessibles à l'utilisateur connecté.
     * Le superadmin voit toutes les fermes.
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        $farms = Farm::query()
            ->with(['owner', 'users'])
            ->withCount(['animals', 'users'])
            ->when(!$user->hasRole('superadmin'), fn ($q) =>
                // Un utilisateur normal ne voit que ses fermes
                $q->where('owner_id', $user->id)
                  ->orWhereHas('users', fn ($r) =>
                      $r->where('user_id', $user->id)
                  )
            )
            ->when($request->search, fn ($q) =>
                $q->where(fn ($q) =>
                    $q->where('name', 'like', "%{$request->search}%")
                      ->orWhere('location', 'like', "%{$request->search}%")
                )
            )
            ->orderBy('name')
            ->paginate($request->per_page ?? 15);

        return ApiResponse::success([
            'farms' => $farms->map(fn ($farm) => $this->formatFarm($farm)),
            'meta'  => [
                'total'        => $farms->total(),
                'per_page'     => $farms->perPage(),
                'current_page' => $farms->currentPage(),
                'last_page'    => $farms->lastPage(),
            ],
        ], 'Fermes récupérées avec succès.');
    }

    /**
     * Créer une ferme.
     * L'utilisateur connecté devient automatiquement owner.
     */
    public function store(StoreFarmRequest $request)
    {
        $user = auth()->user();

        $farm = Farm::create([
            'name'        => $request->name,
            'location'    => $request->location,
            'description' => $request->description,
            'owner_id'    => $user->id,
        ]);

        // Attacher le owner dans farm_user aussi
        $farm->users()->attach($user->id, ['role' => 'owner']);

        // Attacher les autres utilisateurs si fournis
        if ($request->has('users')) {
            $this->syncFarmUsers($farm, $request->users, excludeId: $user->id);
        }

        return ApiResponse::success(
            $this->formatFarm($farm->load('owner', 'users')->loadCount('animals', 'users')),
            'Ferme créée avec succès.',
            201
        );
    }

    /**
     * Détail d'une ferme — Route Model Binding.
     */
    public function show(Farm $farm)
    {
        $this->authorizeAccess($farm);

        $farm->load('owner', 'users')->loadCount('animals', 'users', 'lots');

        return ApiResponse::success(
            $this->formatFarm($farm, detailed: true),
            'Ferme récupérée avec succès.'
        );
    }

    /**
     * Modifier une ferme — Route Model Binding.
     */
    public function update(UpdateFarmRequest $request, Farm $farm)
    {
        $this->authorizeAccess($farm, ownerOnly: true);

        $farm->update($request->only(['name', 'location', 'description']));

        if ($request->has('users')) {
            $this->syncFarmUsers($farm, $request->users, excludeId: $farm->owner_id);
        }

        return ApiResponse::success(
            $this->formatFarm($farm->load('owner', 'users')->loadCount('animals', 'users')),
            'Ferme mise à jour avec succès.'
        );
    }

    /**
     * Archiver une ferme — soft delete — Route Model Binding.
     */
    public function destroy(Farm $farm)
    {
        $this->authorizeAccess($farm, ownerOnly: true);

        $farm->delete();

        return ApiResponse::success(null, 'Ferme archivée avec succès.');
    }

    /**
     * Lister les fermes archivées.
     */
    public function trashed(Request $request)
    {
        $user = auth()->user();

        $farms = Farm::onlyTrashed()
            ->with(['owner', 'users'])
            ->withCount('users')
            ->when(!$user->hasRole('superadmin'), fn ($q) =>
                $q->where('owner_id', $user->id)
            )
            ->orderBy('deleted_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return ApiResponse::success([
            'farms' => $farms->map(fn ($farm) => $this->formatFarm($farm)),
            'meta'  => [
                'total'        => $farms->total(),
                'per_page'     => $farms->perPage(),
                'current_page' => $farms->currentPage(),
                'last_page'    => $farms->lastPage(),
            ],
        ], 'Fermes archivées récupérées avec succès.');
    }

    /**
     * Restaurer une ferme archivée.
     */
    public function restore(string $id)
    {
        $user  = auth()->user();
        $farm  = Farm::onlyTrashed()->findOrFail($id);

        if (!$user->hasRole('superadmin') && $farm->owner_id !== $user->id) {
            return ApiResponse::error('Accès refusé.', null, 403);
        }

        $farm->restore();

        return ApiResponse::success(
            $this->formatFarm($farm->load('owner', 'users')->loadCount('animals', 'users')),
            'Ferme restaurée avec succès.'
        );
    }

    /**
     * Gérer les membres d'une ferme — ajouter/modifier/retirer.
     */
    public function manageUsers(Request $request, Farm $farm)
    {
        $this->authorizeAccess($farm, ownerOnly: true);

        $request->validate([
            'users'        => 'required|array',
            'users.*.id'   => 'required|uuid|exists:users,id',
            'users.*.role' => 'required|string|in:owner,manager,vet,worker',
        ]);

        $this->syncFarmUsers($farm, $request->users, excludeId: $farm->owner_id);

        return ApiResponse::success(
            $this->formatFarm($farm->load('owner', 'users')->loadCount('animals', 'users')),
            'Membres de la ferme mis à jour avec succès.'
        );
    }

    /**
     * Retirer un utilisateur d'une ferme.
     */
    public function removeUser(Farm $farm, User $user)
    {
        $this->authorizeAccess($farm, ownerOnly: true);

        if ($user->id === $farm->owner_id) {
            return ApiResponse::error(
                "Le propriétaire ne peut pas être retiré de sa propre ferme.",
                null,
                422
            );
        }

        $farm->users()->detach($user->id);

        return ApiResponse::success(null, 'Utilisateur retiré de la ferme avec succès.');
    }

    // =========================================================
    // MÉTHODES PRIVÉES
    // =========================================================

    /**
     * Vérifier si l'utilisateur connecté a accès à la ferme.
     */
    private function authorizeAccess(Farm $farm, bool $ownerOnly = false): void
    {
        $user = auth()->user();

        if ($user->hasRole('superadmin')) return;

        if ($ownerOnly && $farm->owner_id !== $user->id) {
            abort(403, 'Seul le propriétaire peut effectuer cette action.');
        }

        if (!$ownerOnly) {
            $hasAccess = $farm->owner_id === $user->id
                || $farm->users()->where('user_id', $user->id)->exists();

            if (!$hasAccess) {
                abort(403, 'Accès refusé à cette ferme.');
            }
        }
    }

    /**
     * Synchroniser les utilisateurs d'une ferme.
     * Exclut toujours le owner pour éviter de l'écraser.
     */
    private function syncFarmUsers(Farm $farm, array $users, string $excludeId): void
    {
        $syncData = collect($users)
            ->reject(fn ($u) => $u['id'] === $excludeId)
            ->mapWithKeys(fn ($u) => [
                $u['id'] => ['role' => $u['role']]
            ])
            ->toArray();

        // syncWithoutDetaching pour ne pas retirer le owner
        $farm->users()->syncWithoutDetaching($syncData);
    }

    /**
     * Formater une ferme pour la réponse API.
     */
    private function formatFarm(Farm $farm, bool $detailed = false): array
    {
        $data = [
            'id'           => $farm->id,
            'name'         => $farm->name,
            'location'     => $farm->location,
            'description'  => $farm->description,
            'owner'        => $farm->owner ? [
                'id'   => $farm->owner->id,
                'name' => $farm->owner->name,
            ] : null,
            'animals_count' => $farm->animals_count ?? 0,
            'users_count'   => $farm->users_count ?? 0,
            'deleted_at'    => $farm->deleted_at,
            'created_at'    => $farm->created_at,
        ];

        // Détails supplémentaires pour show()
        if ($detailed) {
            $data['users'] = $farm->users->map(fn ($u) => [
                'id'    => $u->id,
                'name'  => $u->name,
                'email' => $u->email,
                'role'  => $u->pivot->role,
            ]);
            $data['lots_count'] = $farm->lots_count ?? 0;
        }

        return $data;
    }
}