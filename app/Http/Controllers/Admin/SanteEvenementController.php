<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\SanteApiService;
use App\Services\Admin\FarmApiService;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;

class SanteEvenementController extends Controller
{
    public function __construct(
        private SanteApiService $santeApi,
        private FarmApiService $farmApiService,
        private ActivityLogService $activityLogService
    ) {}

    /**
     * Liste des événements sanitaires
     */
    public function index(Request $request, string $farm)
    {
        $params = [
            'search'   => $request->search,
            'type'     => $request->type,
            'animal_id' => $request->animal_id,
            'date_debut' => $request->date_debut,
            'date_fin' => $request->date_fin,
            'page'     => $request->page,
            'per_page' => 15,
            'current_farm_id' => $farm,
        ];

        $response = $this->santeApi->getAll($params);

        if (!$response->success) {
            return $this->handleApiError($response);
        }

        // Récupérer les données de la ferme pour le banner
        $farmResponse = app(\App\Services\Admin\FarmApiService::class)->find($farm);
        $farmData = $farmResponse->success ? $farmResponse->data : null;

        return view('admin.sante-evenements.index', [
            'evenements' => $response->data['evenements'] ?? [],
            'meta'       => $response->data['meta'] ?? [],
            'farmId'     => $farm,
            'farm'       => $farmData,
        ]);
    }

    /**
     * Formulaire création
     */
    public function create(Request $request, string $farm)
    {
        $farmsResponse = $this->farmApiService->getAll();

        return view('admin.sante-evenements.create', [
            'farmId' => $farm,
            'farms' => $farmsResponse->success ? ($farmsResponse->data['farms'] ?? []) : [],
        ]);
    }

    /**
     * Enregistrer un événement sanitaire
     */
    public function store(Request $request, string $farm)
    {
        // Ajouter current_farm_id pour l'API
        $data = $request->all();
        $data['current_farm_id'] = $farm;

        $response = $this->santeApi->create($data);

        if (!$response->success) {
            return back()
                ->withErrors($response->data['errors'] ?? [])
                ->with('error', $response->message ?? 'Erreur.')
                ->withInput();
        }

        $evenement = $response->data ?? [];

        // Log activity
        if (isset($evenement['id'])) {
            $this->activityLogService->log(
                'created',
                \App\Models\Evenement::find($evenement['id']),
                null,
                $evenement
            );
        }

        return redirect()
            ->route('admin.sante-evenements.index', ['farm' => $farm])
            ->with('success', 'Événement sanitaire créé avec succès.');
    }

    /**
     * Détail d'un événement sanitaire
     */
    public function show(string $farm, string $id)
    {
        $response = $this->santeApi->find($id);

        if (!$response->success) {
            return redirect()
                ->route('admin.sante-evenements.index', ['farm' => $farm])
                ->with('error', 'Événement sanitaire introuvable.');
        }

        return view('admin.sante-evenements.show', [
            'evenement' => $response->data ?? [],
            'farmId' => $farm,
        ]);
    }

    /**
     * Formulaire édition
     */
    public function edit(string $farm, string $id)
    {
        $response = $this->santeApi->find($id);

        if (!$response->success) {
            return redirect()
                ->route('admin.sante-evenements.index', ['farm' => $farm])
                ->with('error', 'Événement sanitaire introuvable.');
        }

        return view('admin.sante-evenements.edit', [
            'evenement' => $response->data ?? [],
            'farmId' => $farm,
        ]);
    }

    /**
     * Mettre à jour un événement sanitaire
     */
    public function update(Request $request, string $farm, string $id)
    {
        $response = $this->santeApi->update($id, $request->all());

        if (!$response->success) {
            return back()
                ->withErrors($response->data['errors'] ?? [])
                ->with('error', $response->message ?? 'Erreur.')
                ->withInput();
        }

        return redirect()
            ->route('admin.sante-evenements.index', ['farm' => $farm])
            ->with('success', 'Événement sanitaire mis à jour avec succès.');
    }

    /**
     * Archiver un événement sanitaire
     */
    public function destroy(string $farm, string $id)
    {
        $response = $this->santeApi->deleteEvenement($id);

        return redirect()
            ->route('admin.sante-evenements.index', ['farm' => $farm])
            ->with(
                $response->success ? 'success' : 'error',
                $response->success
                    ? 'Événement sanitaire archivé avec succès.'
                    : ($response->message ?? 'Erreur.')
            );
    }

    /**
     * Événements sanitaires archivés
     */
    public function trashed(Request $request, string $farm)
    {
        $params = [
            'search'   => $request->search,
            'page'     => $request->page,
            'per_page' => 15,
            'current_farm_id' => $farm,
        ];

        $response = $this->santeApi->trashed($params);

        if (!$response->success) {
            return $this->handleApiError($response);
        }

        return view('admin.sante-evenements.trashed', [
            'evenements' => $response->data['evenements'] ?? [],
            'meta'       => $response->data['meta'] ?? [],
            'farmId'     => $farm,
        ]);
    }

    /**
     * Restaurer un événement sanitaire archivé
     */
    public function restore(string $farm, string $id)
    {
        $response = $this->santeApi->restore($id);

        return redirect()
            ->route('admin.sante-evenements.index', ['farm' => $farm])
            ->with(
                $response->success ? 'success' : 'error',
                $response->success
                    ? 'Événement sanitaire restauré avec succès.'
                    : ($response->message ?? 'Erreur.')
            );
    }

    /**
     * Statistiques sanitaires
     */
    public function statistiques(string $farm)
    {
        $response = $this->santeApi->statistiques(['current_farm_id' => $farm]);

        if (!$response->success) {
            return $this->handleApiError($response);
        }

        return view('admin.sante-evenements.statistiques', [
            'statistiques' => $response->data ?? [],
            'farmId' => $farm,
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
