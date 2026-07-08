<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Farm\StoreFarmRequest;
use App\Http\Requests\Farm\UpdateFarmRequest;
use App\Helpers\ApiResponse;
use App\Models\Farm;
use App\Models\User;
use App\Services\FarmMembershipService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FarmController extends Controller
{
    private FarmMembershipService $farmMembershipService;

    public function __construct(FarmMembershipService $farmMembershipService)
    {
        $this->farmMembershipService = $farmMembershipService;
    }

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
     *
     * Règles :
     * - Un utilisateur avec permission farm.create peut créer une ferme pour un autre owner (owner_id requis)
     * - Un utilisateur avec permission farm.create NE PEUT PAS créer une ferme pour lui-même
     * - Un superadmin peut créer une ferme pour n'importe quel owner (y compris lui-même)
     * - Si owner_id n'est pas fourni, l'utilisateur connecté devient owner (comportement par défaut)
     * - Si la requête vient de l'admin panel (flag from_admin_panel), les permissions sont contournées
     */
    public function store(StoreFarmRequest $request)
    {
        $user = auth()->user();

        // Déterminer le propriétaire de la ferme
        $ownerId = $request->owner_id ?? $user->id;

        // Si owner_id est fourni et différent de l'utilisateur connecté
        if ($ownerId !== $user->id) {
            // Si la requête vient de l'admin panel, autoriser sans vérification de permission
            if (!$request->from_admin_panel) {
                // Vérifier que l'utilisateur a la permission farm.create OU est superadmin
                if (!$user->can('create', Farm::class) && !$user->hasRole('superadmin')) {
                    return ApiResponse::error(
                        'Vous n\'avez pas la permission de créer une ferme pour un autre propriétaire.',
                        null,
                        403
                    );
                }
            }
        } else {
            // Si owner_id n'est pas fourni ou est égal à l'utilisateur connecté
            // Vérifier que l'utilisateur n'est pas en train d'essayer de créer une ferme pour lui-même
            // alors qu'il a la permission farm.create (ce qui n'est pas autorisé)
            if ($user->can('create', Farm::class) && !$user->hasRole('superadmin')) {
                return ApiResponse::error(
                    'Un utilisateur avec la permission farm.create doit spécifier un propriétaire (owner_id) différent de lui-même.',
                    null,
                    422
                );
            }
        }

        // Vérifier que l'owner_id existe
        $owner = User::find($ownerId);
        if (!$owner) {
            return ApiResponse::error(
                'Le propriétaire spécifié est introuvable.',
                null,
                404
            );
        }

        $farm = Farm::create([
            'name'         => $request->name,
            'location'     => $request->location,
            'description'  => $request->description,
            'type_elevage' => $request->type_elevage,
            'photo'        => $request->photo,
            'owner_id'     => $ownerId,
        ]);

        // Attacher le owner dans farm_user avec le rôle owner
        $farm->users()->attach($ownerId, ['role' => 'owner']);

        // Si l'utilisateur connecté n'est pas le owner, l'attacher avec un rôle par défaut (manager)
        if ($ownerId !== $user->id) {
            $farm->users()->attach($user->id, ['role' => 'manager']);
        }

        // Attacher les autres utilisateurs si fournis
        if ($request->has('users')) {
            $this->syncFarmUsers($farm, $request->users, excludeId: $ownerId);
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
        $this->authorize('view', $farm);

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
        $this->authorize('update', $farm);

        $farm->update($request->only(['name', 'location', 'description', 'type_elevage', 'photo']));

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
        $this->authorize('delete', $farm);

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
        $this->authorize('manageMembers', $farm);

        $request->validate([
            'users'        => 'required|array',
            'users.*.id'   => 'required|uuid|exists:users,id',
            'users.*.role' => 'required|string|in:owner,manager,vet,worker',
        ]);

        $this->syncFarmUsers($farm, $request->users, excludeId: $farm->owner_id);

        return ApiResponse::success(
            $this->formatFarm($farm->load('owner', 'users')->loadCount('animals', 'users'), detailed: true),
            'Membres de la ferme mis à jour avec succès.'
        );
    }

    /**
     * Retirer un utilisateur d'une ferme.
     */
    public function removeUser(Farm $farm, User $user)
    {
        $this->authorize('manageMembers', $farm);

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
     * Synchroniser les utilisateurs d'une ferme.
     * Exclut toujours le owner pour éviter de l'écraser.
     */
    private function syncFarmUsers(Farm $farm, array $users, string $excludeId): void
    {
        $this->farmMembershipService->syncMembers($farm, $users, $excludeId);
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
            'type_elevage' => $farm->type_elevage,
            'photo'        => $farm->photo,
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