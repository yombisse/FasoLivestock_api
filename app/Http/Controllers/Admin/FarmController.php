<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\FarmApiService;
use App\Services\Admin\AnimalApiService;
use App\Services\Admin\DashboardApiService;
use App\Services\Admin\StatisticsApiService;
use App\Mappers\FarmMapper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FarmController extends Controller
{
    public function __construct(
        private FarmApiService $farmApi,
        private AnimalApiService $animalApi,
        private DashboardApiService $dashboardApi,
        private StatisticsApiService $statisticsApi
    ) {}

    /**
     * Liste des fermes
     */
    public function index(Request $request)
    {
        $response = $this->farmApi->getAll($request->only([
            'search', 'page', 'per_page'
        ]));

        if (!$response->success) {
            return $this->handleApiError($response);
        }

        // Normaliser les données API pour Blade
        $viewData = FarmMapper::toCollectionView($response->data);

        return view('admin.farms.index', [
            'farms' => $viewData['farms'] ?? [],
            'meta'  => $viewData['meta']  ?? [],
        ]);
    }

    /**
     * Formulaire création
     */
    public function create()
    {
        // Récupérer l'utilisateur depuis la session admin (pas auth()->user() dans l'admin panel)
        $adminUser = session('admin_user');
        
        if (!$adminUser) {
            return redirect()->route('admin.login')
                ->with('error', 'Session expirée. Veuillez vous reconnecter.');
        }

        // Récupérer l'utilisateur complet depuis la base de données
        $user = \App\Models\User::find($adminUser['id']);
        
        if (!$user) {
            return redirect()->route('admin.login')
                ->with('error', 'Utilisateur introuvable.');
        }

        // Déterminer si l'utilisateur est superadmin
        $isSuperadmin = $user->hasRole('superadmin');

        // Déterminer si l'utilisateur a la permission farm.create
        $hasFarmCreatePermission = $user->can('create', \App\Models\Farm::class);

        // Afficher le champ owner si superadmin ou permission farm.create
        $showOwnerField = $isSuperadmin || $hasFarmCreatePermission;

        // Rendre le champ requis si permission farm.create mais pas superadmin
        $ownerFieldRequired = $hasFarmCreatePermission && !$isSuperadmin;

        // FORCER l'affichage du champ owner pour le test
        $showOwnerField = true;

        // Charger les propriétaires potentiels directement depuis la base (sans passer par l'API)
        $potentialOwners = \App\Models\User::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'photo' => $user->photo,
                'is_superadmin' => $user->hasRole('superadmin'),
                'roles' => $user->roles->pluck('name'),
            ])
            ->toArray();

        return view('admin.farms.create', [
            'showOwnerField' => $showOwnerField,
            'ownerFieldRequired' => $ownerFieldRequired,
            'potentialOwners' => $potentialOwners,
            'currentUser' => [
                'id' => $user->id,
                'name' => $user->name,
                'is_superadmin' => $isSuperadmin,
                'has_farm_create_permission' => $hasFarmCreatePermission,
            ],
        ]);
    }

    /**
     * Enregistrer une ferme
     */
    public function store(Request $request)
    {
        $data = $request->all();

        // Ajouter le flag pour indiquer que la requête vient de l'admin panel
        $data['from_admin_panel'] = true;

        // Gérer l'upload de la photo si présente
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('farms', 'public');
            $data['photo'] = asset('storage/' . $photoPath);
        }

        $response = $this->farmApi->create($data);

        if (!$response->success) {
            return back()
                ->withErrors($response->data['errors'] ?? [])
                ->with('error', $response->message ?? 'Erreur.')
                ->withInput();
        }

        return redirect()
            ->route('admin.farms.index')
            ->with('success', 'Ferme créée avec succès.');
    }

    /**
     * Détail d'une ferme
     */
    public function show(string $id)
    {
        $response = $this->farmApi->find($id);

        if (!$response->success) {
            return redirect()
                ->route('admin.farms.index')
                ->with('error', $response->message ?? 'Ferme introuvable.');
        }

        // Normaliser les données API pour Blade
        $farm = FarmMapper::toView($response->data ?? []);

        return view('admin.farms.show', [
            'farm' => $farm,
        ]);
    }

    /**
     * Formulaire édition
     */
    public function edit(string $id)
    {
        $response = $this->farmApi->find($id);

        if (!$response->success) {
            return redirect()
                ->route('admin.farms.index')
                ->with('error', $response->message ?? 'Ferme introuvable.');
        }

        // Normaliser les données API pour Blade
        $farm = FarmMapper::toView($response->data ?? []);

        return view('admin.farms.edit', [
            'farm' => $farm,
            'farmId' => $id,
        ]);
    }

    /**
     * Mettre à jour une ferme
     */
    public function update(Request $request, string $id)
    {
        $response = $this->farmApi->update($id, $request->all());

        if (!$response->success) {
            return back()
                ->withErrors($response->data['errors'] ?? [])
                ->with('error', $response->message ?? 'Erreur.')
                ->withInput();
        }

        return redirect()
            ->route('admin.farms.index')
            ->with('success', 'Ferme mise à jour avec succès.');
    }

    /**
     * Archiver une ferme
     */
    public function destroy(string $id)
    {
        $response = $this->farmApi->deleteFarm($id);

        return redirect()
            ->route('admin.farms.index')
            ->with(
                $response->success ? 'success' : 'error',
                $response->success
                    ? 'Ferme archivée avec succès.'
                    : ($response->message ?? 'Erreur.')
            );
    }

    /**
     * Fermes archivées
     */
    public function trashed(Request $request)
    {
        $response = $this->farmApi->trashed($request->only(['page', 'per_page']));

        if (!$response->success) {
            return $this->handleApiError($response);
        }

        $data = $response->data ?? [];

        return view('admin.farms.trashed', [
            'farms' => $data['farms'] ?? [],
            'meta'  => $data['meta']  ?? [],
        ]);
    }

    /**
     * Restaurer une ferme
     */
    public function restore(string $id)
    {
        $response = $this->farmApi->restore($id);

        return redirect()
            ->route('admin.farms.index')
            ->with(
                $response->success ? 'success' : 'error',
                $response->success
                    ? 'Ferme restaurée avec succès.'
                    : ($response->message ?? 'Erreur.')
            );
    }

    /**
     * Gérer les membres d'une ferme
     */
    public function manageUsers(Request $request, string $id)
    {
        $response = $this->farmApi->manageUsers($id, $request->users ?? []);

        return back()->with(
            $response->success ? 'success' : 'error',
            $response->success
                ? 'Membres mis à jour avec succès.'
                : ($response->message ?? 'Erreur.')
        );
    }

    /**
     * Retirer un utilisateur d'une ferme
     */
    public function removeUser(string $farmId, string $userId)
    {
        $response = $this->farmApi->removeUser($farmId, $userId);

        return back()->with(
            $response->success ? 'success' : 'error',
            $response->success
                ? 'Utilisateur retiré avec succès.'
                : ($response->message ?? 'Erreur.')
        );
    }

    /**
     * Page d'administration complète d'une ferme
     */
    public function manage(Request $request, string $id)
    {
        // Récupérer la ferme avec ses membres et stats
        $farmResponse = $this->farmApi->find($id);
        if (!$farmResponse->success) {
            return redirect()->route('admin.farms.index')
                ->with('error', 'Ferme introuvable.');
        }

        // Normaliser les données API pour Blade
        $farm = FarmMapper::toView($farmResponse->data ?? []);

        // Récupérer les animaux de cette ferme
        $animalsResponse = $this->animalApi->getAll([
            'farm_id'  => $id,
            'search'   => $request->search,
            'espece_id'=> $request->espece_id,
            'sexe'     => $request->sexe,
            'statut'   => $request->statut,
            'per_page' => 15,
            'page'     => $request->page,
        ]);

        // Récupérer les stats du dashboard pour cette ferme
        // Définir current_farm_id en session avant l'appel
        session(['current_farm_id' => $id]);
        $dashboardResponse = $this->dashboardApi->getDashboard();
        $chartsResponse    = $this->statisticsApi->getDashboardCharts();

        $activeTab = $request->get('tab', 'informations');

        return view('admin.farms.manage', [
            'farm'      => $farm,
            'animals'   => $animalsResponse->data['animals'] ?? [],
            'animalsMeta' => $animalsResponse->data['meta'] ?? [],
            'dashboard' => $dashboardResponse->data ?? [],
            'charts'    => $chartsResponse->data ?? [],
            'activeTab' => $activeTab,
        ]);
    }

    /**
     * Mettre à jour le rôle d'un membre (AJAX)
     */
    public function updateRole(Request $request, string $id)
    {
        $request->validate([
            'user_id' => 'required',
            'role'    => 'required|in:manager,vet,worker',
        ]);

        $response = $this->farmApi->manageUsers($id, [
            ['id' => $request->user_id, 'role' => $request->role]
        ]);

        return response()->json([
            'success' => $response->success,
            'message' => $response->message ?? ($response->success ? 'Rôle mis à jour' : 'Erreur'),
        ]);
    }

    /**
     * Ajouter des membres à la ferme
     */
    public function addMembers(Request $request, string $id)
    {
        // Décoder le JSON envoyé par Alpine.js
        $users = json_decode($request->input('users'), true) ?? [];
        
        $request->merge(['users' => $users]);
        
        $request->validate([
            'users' => 'required|array',
            'users.*.id'   => 'required',
            'users.*.role' => 'required|in:manager,vet,worker',
        ]);

        $response = $this->farmApi->manageUsers($id, $users);

        return redirect()
            ->route('admin.farms.manage', ['id' => $id, 'tab' => 'membres'])
            ->with(
                $response->success ? 'success' : 'error',
                $response->success
                    ? 'Membres ajoutés avec succès.'
                    : ($response->message ?? 'Erreur.')
            );
    }

    /**
     * Retirer un membre (AJAX)
     */
    public function removeMember(Request $request, string $farmId, string $userId)
    {
        $response = $this->farmApi->removeUser($farmId, $userId);

        return response()->json([
            'success' => $response->success,
            'message' => $response->message ?? ($response->success ? 'Membre retiré' : 'Erreur'),
        ]);
    }

    /**
     * Gestion des erreurs API
     */
    private function handleApiError($response)
    {
        if (($response->status ?? 500) === 401) {
            session()->forget(['admin_token', 'admin_user', 'admin_token_expires_at']);

            return redirect()
                ->route('admin.login')
                ->with('error', 'Session expirée.');
        }

        return redirect()->back()->with(
            'error',
            $response->message ?? 'Erreur serveur.'
        );
    }
}