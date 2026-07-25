<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\ActivityLogController as ApiActivityLogController;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    private ApiActivityLogController $apiActivityLogController;

    public function __construct(ApiActivityLogController $apiActivityLogController)
    {
        $this->apiActivityLogController = $apiActivityLogController;
    }

    /**
     * Afficher la page d'index des logs d'activité
     */
    public function index(Request $request, string $farm)
    {
        // Préparer la requête pour l'API
        $request->merge([
            'per_page' => $request->per_page ?? 50,
            'action' => $request->action,
            'model_type' => $request->model_type,
            'user_id' => $request->user_id,
            'date_debut' => $request->date_debut,
            'date_fin' => $request->date_fin,
            'include_global' => $request->include_global ?? true, // Inclure les logs globaux par défaut
            'current_farm_id' => $farm,
        ]);

        // Ajouter le header X-Farm-ID si disponible
        $request->headers->set('X-Farm-ID', $farm);

        // Appeler le contrôleur API
        $response = $this->apiActivityLogController->index($request);
        $data = json_decode($response->getContent(), true);

        // Récupérer les données de la ferme pour le banner
        $farmResponse = app(\App\Services\Admin\FarmApiService::class)->find($farm);
        $farmData = $farmResponse->success ? $farmResponse->data : null;

        return view('admin.logs.index', [
            'logs' => $data['data']['logs'] ?? [],
            'meta' => $data['data']['meta'] ?? [],
            'filters' => [
                'action' => $request->action,
                'model_type' => $request->model_type,
                'user_id' => $request->user_id,
                'date_debut' => $request->date_debut,
                'date_fin' => $request->date_fin,
                'include_global' => $request->include_global,
            ],
            'farmId' => $farm,
            'farm' => $farmData,
        ]);
    }

    /**
     * Afficher les logs filtrés par modèle
     */
    public function parModele(Request $request, string $farm, string $modelType)
    {
        // Préparer la requête pour l'API
        $request->merge([
            'per_page' => $request->per_page ?? 50,
            'action' => $request->action,
            'user_id' => $request->user_id,
            'date_debut' => $request->date_debut,
            'date_fin' => $request->date_fin,
            'current_farm_id' => $farm,
        ]);

        // Ajouter le header X-Farm-ID
        $request->headers->set('X-Farm-ID', $farm);

        // Appeler le contrôleur API
        $response = $this->apiActivityLogController->parModele($request, $modelType);
        $data = json_decode($response->getContent(), true);

        return view('admin.logs.index', [
            'logs' => $data['data']['logs'] ?? [],
            'meta' => $data['data']['meta'] ?? [],
            'filters' => [
                'model_type' => $modelType,
                'action' => $request->action,
                'user_id' => $request->user_id,
                'date_debut' => $request->date_debut,
                'date_fin' => $request->date_fin,
            ],
            'farmId' => $farm,
        ]);
    }
}
