<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\AnimalApiService;
use App\Services\AnimalService;
use App\Services\ActivityLogService;
use App\Services\Admin\FarmApiService;
use Illuminate\Http\Request;
use App\Services\Admin\LotApiService;
use App\Services\Admin\EspeceApiService;
use App\Services\Admin\TransactionApiService;


class AnimalController extends Controller
{
    public function __construct(
        private AnimalApiService $animalApi,
        private AnimalService $animalService,
        private ActivityLogService $activityLogService,
        private FarmApiService $farmApiService
    ) {}

    // =========================================================
    // CRUD DE BASE
    // =========================================================

    /**
     * Liste des animaux
     */
    public function index(Request $request)
    {
        $params = [
            'search'   => $request->search,
            'statut'   => $request->statut,
            'page'     => $request->page,
            'per_page' => 15,
        ];

        $response = $this->animalApi->getAll($params);

        if (!$response->success) {
            return $this->handleApiError($response);
        }

        return view('admin.animals.index', [
            'animals' => $response->data['animals'] ?? [],
            'meta'    => $response->data['meta'] ?? [],
        ]);
    }

    /**
     * Formulaire création — le mode ((achat|enregistrement) est passé en query param
     */
    public function create()
    {
        $farmsResponse   = app(FarmApiService::class)->getAll();
        $especesResponse = app(EspeceApiService::class)->getAll();

        return view('admin.animals.create', [
            'farms'   => $farmsResponse->success  ? ($farmsResponse->data['farms']     ?? []) : [],
            'especes' => $especesResponse->success ? ($especesResponse->data['especes'] ?? []) : [],
            'lots'    => [], // chargés dynamiquement via AJAX selon la ferme choisie
            'farm_id' => request('farm_id'), // Pré-remplir si passé en paramètre
        ]);
    }

    /**
     * Formulaire création par achat
     */
    public function createAchat(Request $request)
    {
        $farmsResponse   = app(FarmApiService::class)->getAll();
        $especesResponse  = app(EspeceApiService::class)->getAll();

        // Pour l'instant, catégories vide - à implémenter via API finance si nécessaire
        $categories = [];

        return view('admin.animals.create-achat', [
            'farms'      => $farmsResponse->success  ? ($farmsResponse->data['farms']     ?? []) : [],
            'especes'    => $especesResponse->success ? ($especesResponse->data['especes'] ?? []) : [],
            'categories' => $categories,
            'lots'       => [], // chargés dynamiquement via AJAX selon la ferme choisie
            'farm_id'    => $request->query('farm_id'), // Pré-remplir si passé en paramètre
        ]);
    }

    /**
     * Formulaire création par naissance
     */
    public function createNaissance(Request $request)
    {
        $farmsResponse   = app(FarmApiService::class)->getAll();
        $especesResponse = app(EspeceApiService::class)->getAll();

        return view('admin.animals.create-naissance', [
            'farms'   => $farmsResponse->success  ? ($farmsResponse->data['farms']     ?? []) : [],
            'especes' => $especesResponse->success ? ($especesResponse->data['especes'] ?? []) : [],
            'lots'    => [], // chargés dynamiquement via AJAX selon la ferme choisie
            'farm_id' => $request->query('farm_id'), // Pré-remplir si passé en paramètre
        ]);
    }

    /**
     * Enregistrer un animal — délègue vers purchase() ou naissance() selon le mode
     */
    public function store(Request $request)
    {
        $mode = $request->input('mode', 'import');

        if ($mode === 'achat') {
            return $this->purchase($request);
        }

        if ($mode === 'naissance') {
            return $this->naissance($request);
        }

        // Mode import : aucun événement créé
        $response = $this->animalApi->create(
            array_merge($request->except(['mode']), ['origine' => 'import'])
        );

        if (!$response->success) {
            return back()
                ->withErrors($response->data['errors'] ?? [])
                ->with('error', $response->message ?? "Erreur lors de l'import.")
                ->withInput();
        }

        $animal = $response->data['animal'] ?? $response->data ?? [];

        return redirect()
            ->route('admin.animals.show', $animal['id'] ?? '')
            ->with('success', 'Animal importé avec succès.');
    }

    /**
     * Achat d'un animal — crée l'animal + Transaction SORTIE + Evenement ACHAT
     */
    public function purchase(Request $request)
    {
        $response = $this->animalApi->purchase($request->except(['mode']));

        if (!$response->success) {
            return back()
                ->withErrors($response->data['errors'] ?? [])
                ->with('error', $response->message ?? "Erreur lors de l'achat.")
                ->withInput();
        }

        $animal = $response->data['animal'] ?? $response->data ?? [];

        // Log activity
        $this->activityLogService->log(
            'created',
            \App\Models\Animal::find($animal['id']),
            null,
            $animal
        );

        return redirect()
            ->route('admin.animals.show', $animal['id'] ?? '')
            ->with('success', 'Animal acheté et enregistré avec succès.');
    }

    /**
     * Naissance d'un animal — crée l'animal + Evenement NAISSANCE
     */
    public function naissance(Request $request)
    {
        $response = $this->animalApi->birth($request->except(['mode']));

        if (!$response->success) {
            return back()
                ->withErrors($response->data['errors'] ?? [])
                ->with('error', $response->message ?? "Erreur lors de l'enregistrement de la naissance.")
                ->withInput();
        }

        $animal = $response->data['animal'] ?? $response->data ?? [];

        // Log activity
        $this->activityLogService->log(
            'created',
            \App\Models\Animal::find($animal['id']),
            null,
            $animal
        );

        return redirect()
            ->route('admin.animals.show', $animal['id'] ?? '')
            ->with('success', 'Animal créé par naissance avec succès.');
    }

    /**
     * Formulaire de vente d'un animal
     */
    public function createSale()
    {
        $farmsResponse = $this->farmApiService->getAll();
        $animalsResponse = $this->animalApi->getAll(['per_page' => 1000]);

        return view('admin.animals.create-sale', [
            'farms' => $farmsResponse->success ? ($farmsResponse->data['farms'] ?? []) : [],
            'animals' => $animalsResponse->success ? ($animalsResponse->data['animals'] ?? []) : [],
        ]);
    }

    /**
     * Vente d'un animal — crée Transaction ENTREE + Evenement VENTE
     */
    public function sell(Request $request)
    {
        $animalId = $request->input('animal_id');
        
        if (!$animalId) {
            return back()
                ->with('error', 'Veuillez sélectionner un animal.')
                ->withInput();
        }

        $data = $request->except(['animal_id', '_token']);
        $response = $this->animalApi->sell($animalId, $data);

        if (!$response->success) {
            return back()
                ->withErrors($response->data['errors'] ?? [])
                ->with('error', $response->message ?? "Erreur lors de la vente.")
                ->withInput();
        }

        return redirect()
            ->route('admin.finance.index')
            ->with('success', 'Animal vendu avec succès.');
    }

    /**
     * Détail d'un animal
     */
    public function show(string $id)
    {
        $response = $this->animalApi->find($id);

        if (!$response->success) {
            return redirect()
                ->route('admin.animals.index')
                ->with('error', 'Animal introuvable.');
        }

        return view('admin.animals.show', [
            'animal' => $response->data ?? [],
        ]);
    }

    /**
     * Formulaire édition
     */
    public function edit(string $id)
    {
        $response = $this->animalApi->find($id);

        if (!$response->success) {
            return redirect()
                ->route('admin.animals.index')
                ->with('error', 'Animal introuvable.');
        }

        $farmsResponse   = app(FarmApiService::class)->getAll();
        $especesResponse = app(EspeceApiService::class)->getAll();
        $lotsResponse    = app(LotApiService::class)->getAll();

        return view('admin.animals.edit', [
            'animal'  => $response->data ?? [],
            'farms'   => $farmsResponse->success   ? ($farmsResponse->data['farms']     ?? []) : [],
            'especes' => $especesResponse->success  ? ($especesResponse->data['especes'] ?? []) : [],
            'lots'    => $lotsResponse->success     ? ($lotsResponse->data['lots']       ?? []) : [],
        ]);
    }

    /**
     * Obtenir les animaux éligibles aux événements sanitaires
     */
    public function getEligibleForSanitaire(Request $request)
    {
        $farmId = $request->query('farm_id');
        $typeEvenement = $request->query('type_evenement');

        if (!$farmId) {
            return response()->json([
                'success' => false,
                'message' => 'farm_id parameter is required'
            ], 400);
        }

        $animals = $this->animalService->getEligiblesForSanitaire($farmId, $typeEvenement);

        return response()->json([
            'success' => true,
            'data' => [
                'animals' => $animals,
                'total' => $animals->count(),
            ]
        ]);
    }

    /**
     * Obtenir les animaux éligibles aux événements de reproduction
     */
    public function getEligibleForReproduction(Request $request)
    {
        $farmId = $request->query('farm_id');
        $typeReproduction = $request->query('type_reproduction');

        if (!$farmId) {
            return response()->json([
                'success' => false,
                'message' => 'farm_id parameter is required'
            ], 400);
        }

        if (!$typeReproduction) {
            return response()->json([
                'success' => false,
                'message' => 'type_reproduction parameter is required'
            ], 400);
        }

        $animals = $this->animalService->getEligiblesForReproduction($farmId, $typeReproduction);

        return response()->json([
            'success' => true,
            'data' => [
                'animals' => $animals,
                'total' => $animals->count(),
            ]
        ]);
    }

    /**
     * Obtenir les mâles éligibles pour saillie
     */
    public function getEligibleMales(Request $request)
    {
        $farmId = $request->query('farm_id');

        if (!$farmId) {
            return response()->json([
                'success' => false,
                'message' => 'farm_id parameter is required'
            ], 400);
        }

        $males = $this->animalService->getEligibleMales($farmId);

        return response()->json([
            'success' => true,
            'data' => [
                'animals' => $males,
                'total' => $males->count(),
            ]
        ]);
    }

    /**
     * Mettre à jour un animal
     */
    public function update(Request $request, string $id)
    {
        $response = $this->animalApi->update($id, $request->all());

        if (!$response->success) {
            return back()
                ->withErrors($response->data['errors'] ?? [])
                ->with('error', $response->message ?? 'Erreur lors de la mise à jour.')
                ->withInput();
        }

        return redirect()
            ->route('admin.animals.show', $id)
            ->with('success', 'Animal mis à jour avec succès.');
    }

    /**
     * Archiver un animal (soft delete)
     */
    public function destroy(string $id)
    {
        $response = $this->animalApi->deleteAnimal($id);

        return redirect()
            ->route('admin.animals.index')
            ->with(
                $response->success ? 'success' : 'error',
                $response->success
                    ? 'Animal archivé avec succès.'
                    : ($response->message ?? "Erreur lors de l'archivage.")
            );
    }

    // =========================================================
    // ARCHIVES
    // =========================================================

    /**
     * Liste des animaux archivés
     */
    public function trashed(Request $request)
    {
        $params = [
            'search'   => $request->search,
            'page'     => $request->page,
            'per_page' => 15,
        ];

        $response = $this->animalApi->trashed($params);

        if (!$response->success) {
            return $this->handleApiError($response);
        }

        return view('admin.animals.trashed', [
            'animals' => $response->data['animals'] ?? [],
            'meta'    => $response->data['meta'] ?? [],
        ]);
    }

    /**
     * Restaurer un animal archivé
     */
    public function restore(string $id)
    {
        $response = $this->animalApi->restore($id);

        return redirect()
            ->route('admin.animals.index')
            ->with(
                $response->success ? 'success' : 'error',
                $response->success
                    ? 'Animal restauré avec succès.'
                    : ($response->message ?? 'Erreur lors de la restauration.')
            );
    }

    // =========================================================
    // ENDPOINTS AJAX
    // =========================================================

    /**
     * Charger les lots d'une ferme donnée (appelé dynamiquement depuis le formulaire)
     */
    public function getLotsByFarm(Request $request)
    {
        $farmId = $request->farm_id;

        $lotsResponse = app(LotApiService::class)->getAll();
        $allLots = $lotsResponse->success ? ($lotsResponse->data['lots'] ?? []) : [];

        $filteredLots = collect($allLots)
            ->filter(fn ($lot) => $lot['farm_id'] === $farmId)
            ->values();

        return response()->json([
            'success' => true,
            'lots'    => $filteredLots,
        ]);
    }

    /**
     * Charger les femelles potentiellement mères, filtrées par espèce et race.
     * Utilisé uniquement en consultation/affichage — plus dans les formulaires de création.
     */
    public function getMothers(Request $request)
    {
        $params = [
            'espece_id' => $request->espece_id,
            'race'      => $request->race,
            'sexe'      => 'femelle',
            'per_page'  => 1000,
        ];

        $response = $this->animalApi->getAll($params);

        return response()->json([
            'success' => true,
            'mothers' => $response->success ? ($response->data['animals'] ?? []) : [],
        ]);
    }

    // =========================================================
    // IMPORT CHEPTEL
    // =========================================================

    public function importWizard()
    {
        $especesResponse = app(EspeceApiService::class)->getAll();
        $especes = $especesResponse->success ? ($especesResponse->data['especes'] ?? []) : [];

        $farmsResponse = app(FarmApiService::class)->getAll();
        $farms = $farmsResponse->success ? ($farmsResponse->data['farms'] ?? []) : [];

        return view('admin.animals.import', compact('especes', 'farms'));
    }

    public function importStore(Request $request)
    {
        $request->validate([
            'farm_id' => 'required|string|min:16|max:20|exists:farms,id',
            'animaux' => 'required|array|min:1|max:500',
        ]);

        try {
            $response = $this->animalApi->importBatch([
                'farm_id' => $request->input('farm_id'),
                'animaux' => $request->input('animaux', []),
            ]);

            $redirect = redirect()->route('admin.animals.index')
                ->with('success', $response->message ?? 'Import terminé.');

            if (!empty($response->erreurs)) {
                $redirect->with('erreurs_import', $response->erreurs);
            }

            return $redirect;

        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Import échoué : ' . $e->getMessage());
        }
    }

    // =========================================================
    // GESTION DES ERREURS API
    // =========================================================

    private function handleApiError($response)
    {
        if (($response->status ?? 500) === 401) {
            session()->forget(['admin_token', 'admin_user', 'admin_token_expires_at']);

            return redirect()
                ->route('admin.login')
                ->with('error', 'Session expirée. Veuillez vous reconnecter.');
        }

        return redirect()->back()->with(
            'error',
            $response->message ?? 'Une erreur serveur est survenue.'
        );
    }
}