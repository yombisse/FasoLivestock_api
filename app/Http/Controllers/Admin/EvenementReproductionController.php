<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\EvenementReproductionApiService;
use App\Services\Admin\FarmApiService;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;

class EvenementReproductionController extends Controller
{
    public function __construct(
        private EvenementReproductionApiService $evenementReproductionApi,
        private FarmApiService $farmApiService,
        private ActivityLogService $activityLogService
    ) {}

    /**
     * Liste des événements de reproduction
     */
    public function index(Request $request)
    {
        $params = [
            'search'   => $request->search,
            'page'     => $request->page,
            'per_page' => 15,
        ];

        $response = $this->evenementReproductionApi->getAll($params);

        if (!$response->success) {
            return $this->handleApiError($response);
        }

        return view('admin.evenements-reproduction.index', [
            'evenements' => $response->data['evenements'] ?? [],
            'meta'       => $response->data['meta'] ?? [],
        ]);
    }

    /**
     * Formulaire création
     */
    public function create(Request $request)
    {
        $farmsResponse = $this->farmApiService->getAll();

        return view('admin.evenements-reproduction.create', [
            'farms' => $farmsResponse->success ? ($farmsResponse->data['farms'] ?? []) : [],
        ]);
    }

    /**
     * Enregistrer un événement de reproduction
     */
    public function store(Request $request)
    {
        // Convertir le type en type_evenement_id
        $data = $request->all();
        $type = $data['type'] ?? \App\Models\Evenement::TYPE_SAILLIE;
        
        // Convertir en titre (première lettre majuscule) pour correspondre à la base de données
        $type = ucfirst(strtolower($type));
        
        // Trouver le type_evenement_id correspondant en utilisant la méthode du modèle
        $typeEvenementId = \App\Models\Evenement::getTypeIdByNom($type);
        
        if (!$typeEvenementId) {
            return back()
                ->with('error', 'Type d\'événement introuvable: ' . $type)
                ->withInput();
        }
        
        $data['type_evenement_id'] = $typeEvenementId;
        $data['current_farm_id'] = $request->input('farm_id');
        
        // Supprimer le champ type qui n'est pas utilisé par l'API
        unset($data['type']);

        $response = $this->evenementReproductionApi->create($data);

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
            ->route('admin.evenements-reproduction.index')
            ->with('success', 'Événement de reproduction créé avec succès.');
    }

    /**
     * Détail d'un événement de reproduction
     */
    public function show(string $id)
    {
        $response = $this->evenementReproductionApi->find($id);

        if (!$response->success) {
            return redirect()
                ->route('admin.evenements-reproduction.index')
                ->with('error', 'Événement de reproduction introuvable.');
        }

        return view('admin.evenements-reproduction.show', [
            'evenement' => $response->data ?? [],
        ]);
    }

    /**
     * Formulaire édition
     */
    public function edit(string $id)
    {
        $response = $this->evenementReproductionApi->find($id);

        if (!$response->success) {
            return redirect()
                ->route('admin.evenements-reproduction.index')
                ->with('error', 'Événement de reproduction introuvable.');
        }

        return view('admin.evenements-reproduction.edit', [
            'evenement' => $response->data ?? [],
        ]);
    }

    /**
     * Mettre à jour un événement de reproduction
     */
    public function update(Request $request, string $id)
    {
        $response = $this->evenementReproductionApi->update($id, $request->all());

        if (!$response->success) {
            return back()
                ->withErrors($response->data['errors'] ?? [])
                ->with('error', $response->message ?? 'Erreur.')
                ->withInput();
        }

        return redirect()
            ->route('admin.evenements-reproduction.index')
            ->with('success', 'Événement de reproduction mis à jour avec succès.');
    }

    /**
     * Archiver un événement de reproduction
     */
    public function destroy(string $id)
    {
        $response = $this->evenementReproductionApi->deleteEvenement($id);

        return redirect()
            ->route('admin.evenements-reproduction.index')
            ->with(
                $response->success ? 'success' : 'error',
                $response->success
                    ? 'Événement de reproduction archivé avec succès.'
                    : ($response->message ?? 'Erreur.')
            );
    }

    /**
     * Événements de reproduction archivés
     */
    public function trashed(Request $request)
    {
        $params = [
            'search'   => $request->search,
            'page'     => $request->page,
            'per_page' => 15,
        ];

        $response = $this->evenementReproductionApi->trashed($params);

        if (!$response->success) {
            return $this->handleApiError($response);
        }

        return view('admin.evenements-reproduction.trashed', [
            'evenements' => $response->data['evenements'] ?? [],
            'meta'       => $response->data['meta'] ?? [],
        ]);
    }

    /**
     * Restaurer un événement de reproduction archivé
     */
    public function restore(string $id)
    {
        $response = $this->evenementReproductionApi->restore($id);

        return redirect()
            ->route('admin.evenements-reproduction.index')
            ->with(
                $response->success ? 'success' : 'error',
                $response->success
                    ? 'Événement de reproduction restauré avec succès.'
                    : ($response->message ?? 'Erreur.')
            );
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
