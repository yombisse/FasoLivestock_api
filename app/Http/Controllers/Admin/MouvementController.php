<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\MouvementApiService;
use Illuminate\Http\Request;

class MouvementController extends Controller
{
    public function __construct(
        private MouvementApiService $mouvementApi
    ) {}

    /**
     * Liste des mouvements
     */
    public function index(Request $request, string $farm)
    {
        $params = [
            'search'   => $request->search,
            'page'     => $request->page,
            'per_page' => 15,
            'current_farm_id' => $farm,
        ];

        $response = $this->mouvementApi->getAll($params);

        if (!$response->success) {
            return $this->handleApiError($response);
        }

        // Récupérer les données de la ferme pour le banner
        $farmResponse = app(\App\Services\Admin\FarmApiService::class)->find($farm);
        $farmData = $farmResponse->success ? $farmResponse->data : null;

        return view('admin.mouvements.index', [
            'mouvements' => $response->data['mouvements'] ?? [],
            'meta'       => $response->data['meta'] ?? [],
            'farmId'     => $farm,
            'farm'       => $farmData,
        ]);
    }

    /**
     * Formulaire création (désactivé : mouvements non gérés manuellement via UI)
     */
    public function create()
    {
        abort(403, 'Création de mouvements désactivée via l\'interface web.');
    }

    /**
     * Enregistrer un mouvement (désactivé : mouvements non gérés manuellement via UI)
     */
    public function store(Request $request)
    {
        abort(403, 'Création de mouvements désactivée via l\'interface web.');
    }

    /**
     * Détail d'un mouvement
     */
    public function show(string $farm, string $id)
    {
        $response = $this->mouvementApi->find($id);

        if (!$response->success) {
            return redirect()
                ->route('admin.mouvements.index', ['farm' => $farm])
                ->with('error', 'Mouvement introuvable.');
        }

        return view('admin.mouvements.show', [
            'mouvement' => $response->data ?? [],
            'farmId' => $farm,
        ]);
    }

    /**
     * Formulaire édition (désactivé : mouvements non gérés manuellement via UI)
     */
    public function edit(string $id)
    {
        abort(403, 'Modification de mouvements désactivée via l\'interface web.');
    }

    /**
     * Mettre à jour un mouvement (désactivé : mouvements non gérés manuellement via UI)
     */
    public function update(Request $request, string $id)
    {
        abort(403, 'Modification de mouvements désactivée via l\'interface web.');
    }

    /**
     * Archiver un mouvement (désactivé : mouvements non gérés manuellement via UI)
     */
    public function destroy(string $id)
    {
        abort(403, 'Suppression/archivage de mouvements désactivé via l\'interface web.');
    }


    /**
     * Historique des mouvements d'un animal
     */
    public function animalHistory(string $farm, string $animalId)
    {
        $response = $this->mouvementApi->animalHistory($animalId);

        if (!$response->success) {
            return $this->handleApiError($response);
        }

        return view('admin.mouvements.animal-history', [
            'animal'      => $response->data['animal'] ?? [],
            'historique'  => $response->data['historique'] ?? [],
            'farmId'      => $farm,
        ]);
    }

    /**
     * Traçabilité complète d'un animal
     */
    public function trace(string $farm, string $animalId)
    {
        $response = $this->mouvementApi->trace($animalId);

        if (!$response->success) {
            return $this->handleApiError($response);
        }

        return view('admin.mouvements.trace', [
            'animal'  => $response->data['animal'] ?? [],
            'trace'   => $response->data['trace'] ?? [],
            'farmId' => $farm,
        ]);
    }

    /**
     * Statistiques des mouvements
     */
    public function statistiques()
    {
        $response = $this->mouvementApi->statistiques();

        if (!$response->success) {
            return $this->handleApiError($response);
        }

        return view('admin.mouvements.statistiques', [
            'statistiques' => $response->data ?? [],
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
