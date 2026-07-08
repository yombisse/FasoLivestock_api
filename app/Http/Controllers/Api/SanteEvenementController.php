<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Helpers\ApiResponse;
use App\Http\Requests\Sante\VaccinationRequest;
use App\Http\Requests\Sante\TraitementRequest;
use App\Http\Requests\Sante\MaladieRequest;
use App\Http\Requests\Sante\ControleRequest;
use App\Models\Animal;
use App\Services\SanteEvenementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SanteEvenementController extends Controller
{
    private SanteEvenementService $santeEvenementService;

    public function __construct(SanteEvenementService $santeEvenementService)
    {
        $this->santeEvenementService = $santeEvenementService;
    }

    // =========================================================
    // MÉTHODES PUBLIQUES
    // =========================================================

    /**
     * Obtenir la liste des événements sanitaires pour la ferme courante.
     */
    public function index(Request $request)
    {
        $farmId = $request->input('current_farm_id');

        if (!$farmId) {
            return ApiResponse::error(null, 'Aucune ferme courante définie.', 400);
        }

        $filters = [
            'type' => $request->type,
            'animal_id' => $request->animal_id,
            'date_debut' => $request->date_debut,
            'date_fin' => $request->date_fin,
        ];

        $evenements = $this->santeEvenementService->index($farmId, $filters);

        return ApiResponse::success($evenements, 'Événements sanitaires récupérés avec succès.');
    }

    /**
     * Créer un événement sanitaire.
     * LEGACY: Cette méthode devrait être remplacée par le canal sync push/pull.
     */
    public function store(Request $request)
    {
        $farmId = $request->input('current_farm_id');

        if (!$farmId) {
            return ApiResponse::error(null, 'Aucune ferme courante définie.', 400);
        }

        // Log d'avertissement pour appel hors canal sync
        Log::warning('REST API appelée hors canal sync', [
            'endpoint' => 'POST /sante/evenements',
            'user_id' => auth()->id(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // Validation selon le type d'événement
        $type = $request->type;
        
        switch ($type) {
            case 'vaccination':
                $validated = (new VaccinationRequest($request))->validated();
                break;
            case 'traitement':
                $validated = (new TraitementRequest($request))->validated();
                break;
            case 'maladie':
                $validated = (new MaladieRequest($request))->validated();
                break;
            case 'controle':
                $validated = (new ControleRequest($request))->validated();
                break;
            default:
                return ApiResponse::error(null, 'Type d\'événement invalide.', 400);
        }

        $evenement = $this->santeEvenementService->store($farmId, $validated);

        return ApiResponse::success($evenement, 'Événement sanitaire créé avec succès.', 201);
    }

    /**
     * Obtenir un événement sanitaire.
     */
    public function show(string $evenementId)
    {
        $evenement = $this->santeEvenementService->show($evenementId);

        return ApiResponse::success($evenement, 'Événement sanitaire récupéré avec succès.');
    }

    /**
     * Modifier un événement sanitaire.
     * LEGACY: Cette méthode devrait être remplacée par le canal sync push/pull.
     */
    public function update(Request $request, string $evenementId)
    {
        // Log d'avertissement pour appel hors canal sync
        Log::warning('REST API appelée hors canal sync', [
            'endpoint' => 'PUT /sante/evenements/{id}',
            'user_id' => auth()->id(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'evenement_id' => $evenementId,
        ]);

        $request->validate([
            'type' => 'nullable|in:vaccination,traitement,maladie,controle',
            'date_evenement' => 'nullable|date',
            'description' => 'nullable|string',
            'cout' => 'nullable|numeric|min:0',
        ]);

        $evenement = $this->santeEvenementService->update($evenementId, $request->all());

        return ApiResponse::success($evenement, 'Événement sanitaire modifié avec succès.');
    }

    /**
     * Supprimer un événement sanitaire.
     * LEGACY: Cette méthode devrait être remplacée par le canal sync push/pull.
     */
    public function destroy(string $evenementId)
    {
        // Log d'avertissement pour appel hors canal sync
        Log::warning('REST API appelée hors canal sync', [
            'endpoint' => 'DELETE /sante/evenements/{id}',
            'user_id' => auth()->id(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'evenement_id' => $evenementId,
        ]);

        $this->santeEvenementService->destroy($evenementId);

        return ApiResponse::success(null, 'Événement sanitaire supprimé avec succès.');
    }

    // =========================================================
    // ENDPOINTS SPÉCIFIQUES PAR TYPE
    // =========================================================

    /**
     * Obtenir les vaccinations d'un animal.
     */
    public function vaccinationsAnimal(Animal $animal)
    {
        $this->authorize('view', $animal);

        $vaccinations = $this->santeEvenementService->vaccinationsAnimal($animal->id);

        return ApiResponse::success($vaccinations, 'Vaccinations de l\'animal récupérées avec succès.');
    }

    /**
     * Obtenir les traitements d'un animal.
     */
    public function traitementsAnimal(Animal $animal)
    {
        $this->authorize('view', $animal);

        $traitements = $this->santeEvenementService->traitementsAnimal($animal->id);

        return ApiResponse::success($traitements, 'Traitements de l\'animal récupérés avec succès.');
    }

    /**
     * Obtenir les maladies d'un animal.
     */
    public function maladiesAnimal(Animal $animal)
    {
        $this->authorize('view', $animal);

        $maladies = $this->santeEvenementService->maladiesAnimal($animal->id);

        return ApiResponse::success($maladies, 'Maladies de l\'animal récupérées avec succès.');
    }

    /**
     * Obtenir les consultations d'un animal.
     */
    public function consultationsAnimal(Animal $animal)
    {
        $this->authorize('view', $animal);

        $consultations = $this->santeEvenementService->consultationsAnimal($animal->id);

        return ApiResponse::success($consultations, 'Consultations de l\'animal récupérées avec succès.');
    }

    /**
     * Obtenir les statistiques sanitaires par type pour la ferme courante.
     */
    public function statistiquesParType(Request $request)
    {
        $farmId = $request->input('current_farm_id');

        if (!$farmId) {
            return ApiResponse::error(null, 'Aucune ferme courante définie.', 400);
        }

        $statistiques = $this->santeEvenementService->statistiquesParType(
            $farmId,
            $request->date_debut,
            $request->date_fin
        );

        return ApiResponse::success($statistiques, 'Statistiques sanitaires par type récupérées avec succès.');
    }
}
