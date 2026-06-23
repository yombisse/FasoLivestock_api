<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Espece\StoreEspeceRequest;
use App\Http\Requests\Espece\UpdateEspeceRequest;
use App\Http\Requests\Espece\UpdateEspeceParametreRequest;
use App\Helpers\ApiResponse;
use App\Models\Espece;
use App\Services\EspeceService;
use Illuminate\Http\Request;

class EspeceController extends Controller
{
    private EspeceService $especeService;

    public function __construct(EspeceService $especeService)
    {
        $this->especeService = $especeService;
    }

    // =========================================================
    // MÉTHODES PUBLIQUES
    // =========================================================

    /**
     * Lister toutes les espèces avec pagination et recherche.
     */
    public function index(Request $request)
    {
        $filters = [
            'search' => $request->search,
        ];

        $especes = $this->especeService->index($filters, $request->per_page ?? 15);

        return ApiResponse::success([
            'especes' => $especes->map(fn ($espece) => $this->especeService->formatEspece($espece)),
            'meta'  => [
                'total'        => $especes->total(),
                'per_page'     => $especes->perPage(),
                'current_page' => $especes->currentPage(),
                'last_page'    => $especes->lastPage(),
            ],
        ], 'Espèces récupérées avec succès.');
    }

    /**
     * Créer une espèce.
     */
    public function store(StoreEspeceRequest $request)
    {
        $this->authorize('create', Espece::class);

        $espece = $this->especeService->store($request->validated());

        return ApiResponse::success(
            $this->especeService->formatEspece($espece->load('parametre')),
            'Espèce créée avec succès.',
            201
        );
    }

    /**
     * Détail d'une espèce — Route Model Binding.
     */
    public function show(Espece $espece)
    {
        $this->authorize('view', $espece);

        $espece->load('parametre');

        return ApiResponse::success(
            $this->especeService->formatEspece($espece),
            'Espèce récupérée avec succès.'
        );
    }

    /**
     * Modifier une espèce — Route Model Binding.
     */
    public function update(UpdateEspeceRequest $request, Espece $espece)
    {
        $this->authorize('update', $espece);

        $espece = $this->especeService->update($espece, $request->validated());

        return ApiResponse::success(
            $this->especeService->formatEspece($espece->load('parametre')),
            'Espèce mise à jour avec succès.'
        );
    }

    /**
     * Archiver une espèce — soft delete — Route Model Binding.
     */
    public function destroy(Espece $espece)
    {
        $this->authorize('delete', $espece);

        $this->especeService->destroy($espece);

        return ApiResponse::success(null, 'Espèce archivée avec succès.');
    }

    /**
     * Lister les espèces archivées.
     */
    public function trashed(Request $request)
    {
        $filters = [
            'search' => $request->search,
        ];

        $especes = $this->especeService->trashed($filters, $request->per_page ?? 15);

        return ApiResponse::success([
            'especes' => $especes->map(fn ($espece) => $this->especeService->formatEspece($espece)),
            'meta'  => [
                'total'        => $especes->total(),
                'per_page'     => $especes->perPage(),
                'current_page' => $especes->currentPage(),
                'last_page'    => $especes->lastPage(),
            ],
        ], 'Espèces archivées récupérées avec succès.');
    }

    /**
     * Restaurer une espèce archivée.
     */
    public function restore(string $id)
    {
        $this->authorize('restore', Espece::class);

        $espece = $this->especeService->restore($id);

        return ApiResponse::success(
            $this->especeService->formatEspece($espece->load('parametre')),
            'Espèce restaurée avec succès.'
        );
    }

    /**
     * Afficher les paramètres d'une espèce.
     */
    public function showParametres(Espece $espece)
    {
        $this->authorize('view', $espece);

        $parametres = $this->especeService->showParametres($espece);

        if (!$parametres) {
            return ApiResponse::success(null, 'Aucun paramètre défini pour cette espèce.');
        }

        return ApiResponse::success(
            $this->especeService->formatParametres($parametres),
            'Paramètres récupérés avec succès.'
        );
    }

    /**
     * Créer ou mettre à jour les paramètres d'une espèce.
     */
    public function updateParametres(UpdateEspeceParametreRequest $request, Espece $espece)
    {
        $this->authorize('update', $espece);

        $parametres = $this->especeService->updateParametres($espece, $request->validated());

        return ApiResponse::success(
            $this->especeService->formatParametres($parametres),
            'Paramètres mis à jour avec succès.'
        );
    }
}
