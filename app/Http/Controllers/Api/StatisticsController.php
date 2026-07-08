<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Helpers\ApiResponse;
use App\Services\StatisticsService;
use Illuminate\Http\Request;

class StatisticsController extends Controller
{
    private StatisticsService $statisticsService;

    public function __construct(StatisticsService $statisticsService)
    {
        $this->statisticsService = $statisticsService;
    }

    /**
     * Obtenir toutes les données pour le tableau de bord graphique.
     * Accepte farm_id en paramètre ou utilise la ferme courante du contexte.
     */
    public function dashboardCharts(Request $request)
    {
        $farmId = $request->input('farm_id') ?? $request->input('current_farm_id');

        if (!$farmId) {
            return ApiResponse::error(null, 'Aucune ferme spécifiée ou ferme courante définie.', 400);
        }

        // Vérifier que l'utilisateur a accès à cette ferme
        $user = auth()->user();
        $farm = \App\Models\Farm::findOrFail($farmId);
        
        if (!$user->hasRole('superadmin') && 
            $farm->owner_id !== $user->id && 
            !$farm->users()->where('user_id', $user->id)->exists()) {
            return ApiResponse::error(null, 'Vous n\'avez pas accès à cette ferme.', 403);
        }

        $charts = $this->statisticsService->dashboardCharts(
            $farmId,
            $request->date_debut,
            $request->date_fin
        );

        return ApiResponse::success($charts, 'Données graphiques du tableau de bord récupérées avec succès.');
    }

    /**
     * Obtenir les données pour le graphique d'évolution financière.
     * Accepte farm_id en paramètre ou utilise la ferme courante du contexte.
     */
    public function financialEvolution(Request $request)
    {
        $farmId = $request->input('farm_id') ?? $request->input('current_farm_id');

        if (!$farmId) {
            return ApiResponse::error(null, 'Aucune ferme spécifiée ou ferme courante définie.', 400);
        }

        // Vérifier que l'utilisateur a accès à cette ferme
        $user = auth()->user();
        $farm = \App\Models\Farm::findOrFail($farmId);
        
        if (!$user->hasRole('superadmin') && 
            $farm->owner_id !== $user->id && 
            !$farm->users()->where('user_id', $user->id)->exists()) {
            return ApiResponse::error(null, 'Vous n\'avez pas accès à cette ferme.', 403);
        }

        $request->validate([
            'period' => 'nullable|in:daily,weekly,monthly,yearly',
        ]);

        $chart = $this->statisticsService->financialEvolution(
            $farmId,
            $request->date_debut,
            $request->date_fin,
            $request->period ?? 'monthly'
        );

        return ApiResponse::success($chart, 'Graphique d\'évolution financière récupéré avec succès.');
    }

    /**
     * Obtenir les données pour le graphique des revenus par catégorie.
     * Accepte farm_id en paramètre ou utilise la ferme courante du contexte.
     */
    public function revenueByCategory(Request $request)
    {
        $farmId = $request->input('farm_id') ?? $request->input('current_farm_id');

        if (!$farmId) {
            return ApiResponse::error(null, 'Aucune ferme spécifiée ou ferme courante définie.', 400);
        }

        // Vérifier que l'utilisateur a accès à cette ferme
        $user = auth()->user();
        $farm = \App\Models\Farm::findOrFail($farmId);
        
        if (!$user->hasRole('superadmin') && 
            $farm->owner_id !== $user->id && 
            !$farm->users()->where('user_id', $user->id)->exists()) {
            return ApiResponse::error(null, 'Vous n\'avez pas accès à cette ferme.', 403);
        }

        $chart = $this->statisticsService->revenueByCategory(
            $farmId,
            $request->date_debut,
            $request->date_fin
        );

        return ApiResponse::success($chart, 'Graphique des revenus par catégorie récupéré avec succès.');
    }

    /**
     * Obtenir les données pour le graphique des charges par catégorie.
     * Accepte farm_id en paramètre ou utilise la ferme courante du contexte.
     */
    public function expenseByCategory(Request $request)
    {
        $farmId = $request->input('farm_id') ?? $request->input('current_farm_id');

        if (!$farmId) {
            return ApiResponse::error(null, 'Aucune ferme spécifiée ou ferme courante définie.', 400);
        }

        // Vérifier que l'utilisateur a accès à cette ferme
        $user = auth()->user();
        $farm = \App\Models\Farm::findOrFail($farmId);
        
        if (!$user->hasRole('superadmin') && 
            $farm->owner_id !== $user->id && 
            !$farm->users()->where('user_id', $user->id)->exists()) {
            return ApiResponse::error(null, 'Vous n\'avez pas accès à cette ferme.', 403);
        }

        $chart = $this->statisticsService->expenseByCategory(
            $farmId,
            $request->date_debut,
            $request->date_fin
        );

        return ApiResponse::success($chart, 'Graphique des charges par catégorie récupéré avec succès.');
    }

    /**
     * Obtenir les données pour le graphique du cheptel par espèce.
     * Accepte farm_id en paramètre ou utilise la ferme courante du contexte.
     */
    public function herdBySpecies(Request $request)
    {
        $farmId = $request->input('farm_id') ?? $request->input('current_farm_id');

        if (!$farmId) {
            return ApiResponse::error(null, 'Aucune ferme spécifiée ou ferme courante définie.', 400);
        }

        // Vérifier que l'utilisateur a accès à cette ferme
        $user = auth()->user();
        $farm = \App\Models\Farm::findOrFail($farmId);
        
        if (!$user->hasRole('superadmin') && 
            $farm->owner_id !== $user->id && 
            !$farm->users()->where('user_id', $user->id)->exists()) {
            return ApiResponse::error(null, 'Vous n\'avez pas accès à cette ferme.', 403);
        }

        $chart = $this->statisticsService->herdBySpecies($farmId);

        return ApiResponse::success($chart, 'Graphique du cheptel par espèce récupéré avec succès.');
    }

    /**
     * Obtenir les données pour le graphique du cheptel par sexe.
     * Accepte farm_id en paramètre ou utilise la ferme courante du contexte.
     */
    public function herdBySex(Request $request)
    {
        $farmId = $request->input('farm_id') ?? $request->input('current_farm_id');

        if (!$farmId) {
            return ApiResponse::error(null, 'Aucune ferme spécifiée ou ferme courante définie.', 400);
        }

        // Vérifier que l'utilisateur a accès à cette ferme
        $user = auth()->user();
        $farm = \App\Models\Farm::findOrFail($farmId);
        
        if (!$user->hasRole('superadmin') && 
            $farm->owner_id !== $user->id && 
            !$farm->users()->where('user_id', $user->id)->exists()) {
            return ApiResponse::error(null, 'Vous n\'avez pas accès à cette ferme.', 403);
        }

        $chart = $this->statisticsService->herdBySex($farmId);

        return ApiResponse::success($chart, 'Graphique du cheptel par sexe récupéré avec succès.');
    }

    /**
     * Obtenir les données pour le graphique des mouvements.
     * Accepte farm_id en paramètre ou utilise la ferme courante du contexte.
     */
    public function movementsStats(Request $request)
    {
        $farmId = $request->input('farm_id') ?? $request->input('current_farm_id');

        if (!$farmId) {
            return ApiResponse::error(null, 'Aucune ferme spécifiée ou ferme courante définie.', 400);
        }

        // Vérifier que l'utilisateur a accès à cette ferme
        $user = auth()->user();
        $farm = \App\Models\Farm::findOrFail($farmId);
        
        if (!$user->hasRole('superadmin') && 
            $farm->owner_id !== $user->id && 
            !$farm->users()->where('user_id', $user->id)->exists()) {
            return ApiResponse::error(null, 'Vous n\'avez pas accès à cette ferme.', 403);
        }

        $request->validate([
            'period' => 'nullable|in:daily,weekly,monthly,yearly',
        ]);

        $chart = $this->statisticsService->movementsStats(
            $farmId,
            $request->date_debut,
            $request->date_fin,
            $request->period ?? 'monthly'
        );

        return ApiResponse::success($chart, 'Graphique des mouvements récupéré avec succès.');
    }

    /**
     * Obtenir les données pour le graphique des événements sanitaires.
     * Accepte farm_id en paramètre ou utilise la ferme courante du contexte.
     */
    public function healthEventsEvolution(Request $request)
    {
        $farmId = $request->input('farm_id') ?? $request->input('current_farm_id');

        if (!$farmId) {
            return ApiResponse::error(null, 'Aucune ferme spécifiée ou ferme courante définie.', 400);
        }

        // Vérifier que l'utilisateur a accès à cette ferme
        $user = auth()->user();
        $farm = \App\Models\Farm::findOrFail($farmId);
        
        if (!$user->hasRole('superadmin') && 
            $farm->owner_id !== $user->id && 
            !$farm->users()->where('user_id', $user->id)->exists()) {
            return ApiResponse::error(null, 'Vous n\'avez pas accès à cette ferme.', 403);
        }

        $request->validate([
            'period' => 'nullable|in:daily,weekly,monthly,yearly',
        ]);

        $chart = $this->statisticsService->healthEventsEvolution(
            $farmId,
            $request->date_debut,
            $request->date_fin,
            $request->period ?? 'monthly'
        );

        return ApiResponse::success($chart, 'Graphique des événements sanitaires récupéré avec succès.');
    }

    /**
     * Obtenir les données pour le graphique de reproduction.
     * Accepte farm_id en paramètre ou utilise la ferme courante du contexte.
     */
    public function reproductionStats(Request $request)
    {
        $farmId = $request->input('farm_id') ?? $request->input('current_farm_id');

        if (!$farmId) {
            return ApiResponse::error(null, 'Aucune ferme spécifiée ou ferme courante définie.', 400);
        }

        // Vérifier que l'utilisateur a accès à cette ferme
        $user = auth()->user();
        $farm = \App\Models\Farm::findOrFail($farmId);
        
        if (!$user->hasRole('superadmin') && 
            $farm->owner_id !== $user->id && 
            !$farm->users()->where('user_id', $user->id)->exists()) {
            return ApiResponse::error(null, 'Vous n\'avez pas accès à cette ferme.', 403);
        }

        $chart = $this->statisticsService->reproductionStats(
            $farmId,
            $request->date_debut,
            $request->date_fin
        );

        return ApiResponse::success($chart, 'Graphique de reproduction récupéré avec succès.');
    }

    /**
     * Obtenir les données pour le graphique des naissances par mois.
     * Accepte farm_id en paramètre ou utilise la ferme courante du contexte.
     */
    public function birthsByMonth(Request $request)
    {
        $farmId = $request->input('farm_id') ?? $request->input('current_farm_id');

        if (!$farmId) {
            return ApiResponse::error(null, 'Aucune ferme spécifiée ou ferme courante définie.', 400);
        }

        // Vérifier que l'utilisateur a accès à cette ferme
        $user = auth()->user();
        $farm = \App\Models\Farm::findOrFail($farmId);
        
        if (!$user->hasRole('superadmin') && 
            $farm->owner_id !== $user->id && 
            !$farm->users()->where('user_id', $user->id)->exists()) {
            return ApiResponse::error(null, 'Vous n\'avez pas accès à cette ferme.', 403);
        }

        $request->validate([]);

        $months = $request->months ?? 12;

        $chart = $this->statisticsService->birthsByMonth($farmId, $months);

        return ApiResponse::success($chart, 'Graphique des naissances par mois récupéré avec succès.');
    }
}
