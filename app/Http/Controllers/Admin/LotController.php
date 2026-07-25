<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\LotApiService;
use App\Services\Admin\FarmApiService;
use App\Services\Admin\AnimalApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LotController extends Controller
{
    public function __construct(
        private LotApiService $lotApi,
        private FarmApiService $farmApi,
        private AnimalApiService $animalApi
    ) {}

    /**
     * Liste des lots
     */
    public function index(Request $request, string $farm)
    {
        $params = [
            'search'   => $request->search,
            'page'     => $request->page,
            'per_page' => 15,
            'current_farm_id' => $farm,
        ];

        $response = $this->lotApi->getAll($params);
        if (!$response->success) {
            return $this->handleApiError($response);
        }

        // Récupérer les données de la ferme pour le banner
        $farmResponse = app(\App\Services\Admin\FarmApiService::class)->find($farm);
        $farmData = $farmResponse->success ? $farmResponse->data : null;

        return view('admin.lots.index', [
            'lots' => $response->data['lots'] ?? [],
            'meta' => $response->data['meta'] ?? [],
            'farmId' => $farm,
            'farm' => $farmData,
        ]);
    }

    /**
     * Formulaire création
     */
    public function create()
    {
        $farmsResponse = $this->farmApi->getAll();
        $farms = $farmsResponse->success ? ($farmsResponse->data['farms'] ?? []) : [];

        return view('admin.lots.create', [
            'farms' => $farms,
        ]);
    }

    /**
     * Enregistrer un lot
     */
    public function store(Request $request)
    {
        
        $response = $this->lotApi->create($request->all());

        if (!$response->success) {
            return back()
                ->withErrors($response->data['errors'] ?? [])
                ->with('error', $response->message ?? 'Erreur.')
                ->withInput();
        }

        return redirect()
            ->route('admin.lots.index')
            ->with('success', 'Lot créé avec succès.');
    }

    /**
     * Détail d'un lot
     */
    public function show(string $farm, string $id)
    {
        $response = $this->lotApi->find($id);
        if (!$response->success) {
            return redirect()
                ->route('admin.lots.index', ['farm' => $farm])
                ->with('error', 'Lot introuvable.');
        }

        $lot = $response->data;

        // Récupérer les animaux du lot
        $lotAnimalsResponse = $this->lotApi->animals($id);
        $lot['animals'] = $lotAnimalsResponse->success ? ($lotAnimalsResponse->data['animals'] ?? []) : [];

        return view('admin.lots.show', [
            'lot' => $lot,
            'farmId' => $farm,
        ]);
    }

    /**
     * Formulaire édition
     */
    public function edit(string $farm, string $id)
    {
        $response = $this->lotApi->find($id);
        if (!$response->success) {
            return redirect()
                ->route('admin.lots.index', ['farm' => $farm])
                ->with('error', 'Lot introuvable.');
        }

        $farmsResponse = $this->farmApi->getAll();
        $farms = $farmsResponse->success ? ($farmsResponse->data['farms'] ?? []) : [];

        return view('admin.lots.edit', [
            'lot' => $response->data ?? [],
            'farms' => $farms,
            'farmId' => $farm,
        ]);
    }

    /**
     * Mettre à jour un lot
     */
    public function update(Request $request, string $farm, string $id)
    {
        $response = $this->lotApi->update($id, $request->all());

        if (!$response->success) {
            return back()
                ->withErrors($response->data['errors'] ?? [])
                ->with('error', $response->message ?? 'Erreur.')
                ->withInput();
        }

        return redirect()
            ->route('admin.lots.index', ['farm' => $farm])
            ->with('success', 'Lot mis à jour avec succès.');
    }

    /**
     * Archiver un lot
     */
    public function destroy(string $farm, string $id)
    {
        $response = $this->lotApi->deleteLot($id);
        return redirect()
            ->route('admin.lots.index', ['farm' => $farm])
            ->with(
                $response->success ? 'success' : 'error',
                $response->success
                    ? 'Lot archivé avec succès.'
                    : ($response->message ?? 'Erreur.')
            );
    }

    /**
     * Lots archivés
     */
    public function trashed(Request $request)
    {
        $params = [
            'search'   => $request->search,
            'page'     => $request->page,
            'per_page' => 15,
        ];

        $response = $this->lotApi->trashed($params);
        if (!$response->success) {
            return $this->handleApiError($response);
        }

        return view('admin.lots.trashed', [
            'lots' => $response->data['lots'] ?? [],
            'meta' => $response->data['meta'] ?? [],
        ]);
    }

    /**
     * Restaurer un lot archivé
     */
    public function restore(string $id)
    {
        $response = $this->lotApi->restore($id);
        return redirect()
            ->route('admin.lots.index')
            ->with(
                $response->success ? 'success' : 'error',
                $response->success
                    ? 'Lot restauré avec succès.'
                    : ($response->message ?? 'Erreur.')
            );
    }

    /**
     * Formulaire d'affectation d'animaux à un lot
     */
    public function assign(string $farm, string $id)
    {
        $lotResponse = $this->lotApi->find($id);
        if (!$lotResponse->success) {
            return redirect()
                ->route('admin.lots.index', ['farm' => $farm])
                ->with('error', 'Lot introuvable.');
        }

        $lot = $lotResponse->data;

        // Récupérer les animaux de la ferme du lot
        $animalsResponse = $this->animalApi->getAll([
            'farm_id' => $lot['farm_id'],
            'without_lot' => true, // Animaux sans lot
        ]);

        $animals = $animalsResponse->success ? ($animalsResponse->data['animals'] ?? []) : [];

        // Récupérer les animaux déjà dans le lot
        $lotAnimalsResponse = $this->lotApi->animals($id);
        $lotAnimals = $lotAnimalsResponse->success ? ($lotAnimalsResponse->data['animals'] ?? []) : [];

        return view('admin.lots.assign', [
            'lot' => $lot,
            'animals' => $animals,
            'lotAnimals' => $lotAnimals,
            'farmId' => $farm,
        ]);
    }

    /**
     * Affecter des animaux à un lot
     */
    public function storeAssign(Request $request, string $farm, string $id)
    {
        $animalIds = $request->input('animal_ids', []);

        if (empty($animalIds)) {
            return back()
                ->with('error', 'Veuillez sélectionner au moins un animal.');
        }

        $response = $this->lotApi->assignAnimals($id, [
            'animal_ids' => $animalIds,
        ]);

        if (!$response->success) {
            return back()
                ->with('error', $response->message ?? 'Erreur lors de l\'affectation.');
        }

        return redirect()
            ->route('admin.lots.show', ['farm' => $farm, 'lot' => $id])
            ->with('success', 'Animaux affectés avec succès.');
    }

    /**
     * Retirer un animal d'un lot
     */
    public function removeAnimal(string $farm, string $lotId, string $animalId)
    {
        $response = $this->lotApi->removeAnimal($lotId, $animalId);

        return redirect()
            ->route('admin.lots.show', ['farm' => $farm, 'lot' => $lotId])
            ->with(
                $response->success ? 'success' : 'error',
                $response->success
                    ? 'Animal retiré du lot avec succès.'
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
