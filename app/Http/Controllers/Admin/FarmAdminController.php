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

class FarmAdminController extends Controller
{
    public function __construct(
        private FarmApiService $farmApi,
        private AnimalApiService $animalApi,
        private DashboardApiService $dashboardApi,
        private StatisticsApiService $statisticsApi
    ) {}

    /**
     * Gérer les membres d'une ferme
     */
    public function manageUsers(Request $request, string $farm)
    {
        $response = $this->farmApi->manageUsers($farm, $request->users ?? []);

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
    public function removeUser(string $farm, string $userId)
    {
        $response = $this->farmApi->removeUser($farm, $userId);

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
    public function manage(Request $request, string $farm)
    {
        // Récupérer la ferme avec ses membres et stats
        $farmResponse = $this->farmApi->find($farm);
        if (!$farmResponse->success) {
            return redirect()->route('admin.farms.index')
                ->with('error', 'Ferme introuvable.');
        }

        // Normaliser les données API pour Blade
        $farmData = FarmMapper::toView($farmResponse->data ?? []);

        // Récupérer les animaux de cette ferme
        $animalsResponse = $this->animalApi->getAll([
            'farm_id'  => $farm,
            'search'   => $request->search,
            'espece_id'=> $request->espece_id,
            'sexe'     => $request->sexe,
            'statut'   => $request->statut,
            'per_page' => 15,
            'page'     => $request->page,
        ]);

        // Récupérer les stats du dashboard pour cette ferme
        // pont transitoire, à retirer après migration complète des modules farm-scoped (voir étape 5)
        // session(['current_farm_id' => $farm]);
        $dashboardResponse = $this->dashboardApi->getDashboard(['current_farm_id' => $farm]);
        $chartsResponse    = $this->statisticsApi->getDashboardCharts(['current_farm_id' => $farm]);

        $activeTab = $request->get('tab', 'informations');

        return view('admin.farms.manage', [
            'farm'      => $farmData,
            'farmId'    => $farm,
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
    public function updateRole(Request $request, string $farm)
    {
        $request->validate([
            'user_id' => 'required',
            'role_id' => 'required|exists:roles,id',
        ]);

        $response = $this->farmApi->manageUsers($farm, [
            ['id' => $request->user_id, 'role_id' => $request->role_id]
        ]);

        return response()->json([
            'success' => $response->success,
            'message' => $response->message ?? ($response->success ? 'Rôle mis à jour' : 'Erreur'),
        ]);
    }

    /**
     * Ajouter des membres à la ferme
     */
    public function addMembers(Request $request, string $farm)
    {
        // Décoder le JSON envoyé par Alpine.js
        $users = json_decode($request->input('users'), true) ?? [];
        
        $request->merge(['users' => $users]);
        
        // Logging pour diagnostiquer
        \Log::info('addMembers appelé', ['users' => $users]);
        
        $request->validate([
            'users' => 'required|array',
            'users.*.id'    => 'required',
            'users.*.role_id' => 'required|exists:roles,id',
        ]);

        $response = $this->farmApi->manageUsers($farm, $users);
        
        \Log::info('Réponse API manageUsers', ['success' => $response->success, 'message' => $response->message, 'data' => $response->data ?? null]);

        return redirect()
            ->route('admin.farms.manage', ['farm' => $farm, 'tab' => 'membres'])
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
    public function removeMember(Request $request, string $farm, string $userId)
    {
        $response = $this->farmApi->removeUser($farm, $userId);

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
