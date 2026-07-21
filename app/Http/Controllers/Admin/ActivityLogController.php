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
    public function index(Request $request)
    {
        $farmId = session('current_farm_id');
        
        // Préparer la requête pour l'API
        $request->merge([
            'per_page' => $request->per_page ?? 50,
            'action' => $request->action,
            'model_type' => $request->model_type,
            'user_id' => $request->user_id,
            'date_debut' => $request->date_debut,
            'date_fin' => $request->date_fin,
            'include_global' => $request->include_global ?? true, // Inclure les logs globaux par défaut
        ]);

        // Ajouter le header X-Farm-ID si disponible
        if ($farmId) {
            $request->headers->set('X-Farm-ID', $farmId);
        }

        // Appeler le contrôleur API
        $response = $this->apiActivityLogController->index($request);
        $data = json_decode($response->getContent(), true);

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
            'farm_id' => $farmId,
        ]);
    }

    /**
     * Afficher les logs filtrés par modèle
     */
    public function parModele(Request $request, string $modelType)
    {
        $farmId = session('current_farm_id');
        
        if (!$farmId) {
            return view('admin.logs.index')->with('error', 'Aucune ferme sélectionnée');
        }

        // Préparer la requête pour l'API
        $request->merge([
            'per_page' => $request->per_page ?? 50,
            'action' => $request->action,
            'user_id' => $request->user_id,
            'date_debut' => $request->date_debut,
            'date_fin' => $request->date_fin,
        ]);

        // Ajouter le header X-Farm-ID
        $request->headers->set('X-Farm-ID', $farmId);

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
        ]);
    }
}
