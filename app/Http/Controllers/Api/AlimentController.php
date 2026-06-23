<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Alimentation\StoreAlimentRequest;
use App\Http\Requests\Alimentation\UpdateAlimentRequest;
use App\Helpers\ApiResponse;
use App\Models\Aliment;
use App\Services\AlimentService;
use Illuminate\Http\Request;

class AlimentController extends Controller
{
    private AlimentService $alimentService;

    public function __construct(AlimentService $alimentService)
    {
        $this->alimentService = $alimentService;
    }

    // =========================================================
    // MÉTHODES PUBLIQUES
    // =========================================================

    /**
     * Lister tous les aliments avec pagination et filtres.
     */
    public function index(Request $request)
    {
        $filters = [
            'en_rupture' => $request->en_rupture,
            'unite' => $request->unite,
        ];

        $aliments = $this->alimentService->index($filters, $request->per_page ?? 15);

        return ApiResponse::success([
            'aliments' => $aliments->map(fn ($aliment) => $this->alimentService->formatAliment($aliment)),
            'meta'  => [
                'total'        => $aliments->total(),
                'per_page'     => $aliments->perPage(),
                'current_page' => $aliments->currentPage(),
                'last_page'    => $aliments->lastPage(),
            ],
        ], 'Aliments récupérés avec succès.');
    }

    /**
     * Créer un aliment.
     */
    public function store(StoreAlimentRequest $request)
    {
        $this->authorize('create', Aliment::class);

        $userId = auth()->id();
        $data = $request->validated();

        try {
            $aliment = $this->alimentService->store($data, $userId);

            return ApiResponse::success(
                $this->alimentService->formatAliment($aliment->load(['farm', 'rations'])),
                'Aliment créé avec succès.',
                201
            );
        } catch (\Exception $e) {
            return ApiResponse::error(null, $e->getMessage(), 400);
        }
    }

    /**
     * Détail d'un aliment — Route Model Binding.
     */
    public function show(Aliment $aliment)
    {
        $this->authorize('view', $aliment);

        $aliment->load(['farm', 'rations']);

        return ApiResponse::success(
            $this->alimentService->formatAliment($aliment),
            'Aliment récupéré avec succès.'
        );
    }

    /**
     * Modifier un aliment — Route Model Binding.
     */
    public function update(UpdateAlimentRequest $request, Aliment $aliment)
    {
        $this->authorize('update', $aliment);

        $userId = auth()->id();
        $data = $request->validated();

        try {
            $aliment = $this->alimentService->update($aliment, $data, $userId);

            return ApiResponse::success(
                $this->alimentService->formatAliment($aliment->load(['farm', 'rations'])),
                'Aliment mis à jour avec succès.'
            );
        } catch (\Exception $e) {
            return ApiResponse::error(null, $e->getMessage(), 409);
        }
    }

    /**
     * Archiver un aliment — soft delete — Route Model Binding.
     */
    public function destroy(Aliment $aliment)
    {
        $this->authorize('delete', $aliment);

        $this->alimentService->destroy($aliment);

        return ApiResponse::success(null, 'Aliment archivé avec succès.');
    }

    /**
     * Lister les aliments archivés.
     */
    public function trashed(Request $request)
    {
        $filters = [
            'unite' => $request->unite,
        ];

        $aliments = $this->alimentService->trashed($filters, $request->per_page ?? 15);

        return ApiResponse::success([
            'aliments' => $aliments->map(fn ($aliment) => $this->alimentService->formatAliment($aliment)),
            'meta'  => [
                'total'        => $aliments->total(),
                'per_page'     => $aliments->perPage(),
                'current_page' => $aliments->currentPage(),
                'last_page'    => $aliments->lastPage(),
            ],
        ], 'Aliments archivés récupérés avec succès.');
    }

    /**
     * Restaurer un aliment archivé.
     */
    public function restore(string $id)
    {
        $this->authorize('restore', Aliment::class);

        $aliment = $this->alimentService->restore($id);

        return ApiResponse::success(
            $this->alimentService->formatAliment($aliment->load(['farm', 'rations'])),
            'Aliment restauré avec succès.'
        );
    }

    /**
     * Approvisionner le stock d'un aliment.
     */
    public function approvisionner(Request $request, Aliment $aliment)
    {
        $this->authorize('update', $aliment);

        $request->validate([
            'quantite' => 'required|numeric|min:0',
            'note' => 'nullable|string',
        ]);

        $userId = auth()->id();
        $quantite = $request->quantite;
        $note = $request->note;

        try {
            $aliment = $this->alimentService->approvisionner($aliment, $quantite, $userId, $note);

            return ApiResponse::success(
                $this->alimentService->formatAliment($aliment->load(['farm', 'rations'])),
                'Stock approvisionné avec succès.'
            );
        } catch (\Exception $e) {
            return ApiResponse::error(null, $e->getMessage(), 400);
        }
    }

    /**
     * Ajuster manuellement le stock d'un aliment.
     */
    public function ajusterStock(Request $request, Aliment $aliment)
    {
        $this->authorize('update', $aliment);

        $request->validate([
            'nouveau_stock' => 'required|numeric|min:0',
            'note' => 'nullable|string',
        ]);

        $userId = auth()->id();
        $nouveauStock = $request->nouveau_stock;
        $note = $request->note;

        try {
            $aliment = $this->alimentService->ajusterStock($aliment, $nouveauStock, $userId, $note);

            return ApiResponse::success(
                $this->alimentService->formatAliment($aliment->load(['farm', 'rations'])),
                'Stock ajusté avec succès.'
            );
        } catch (\Exception $e) {
            return ApiResponse::error(null, $e->getMessage(), 400);
        }
    }

    /**
     * Obtenir les aliments en rupture de stock.
     */
    public function enRupture(Request $request)
    {
        $this->authorize('view', Aliment::class);

        $aliments = $this->alimentService->enRupture();

        return ApiResponse::success([
            'aliments' => $aliments,
        ], 'Aliments en rupture de stock récupérés avec succès.');
    }

    /**
     * Obtenir l'historique des mouvements de stock d'un aliment.
     */
    public function historiqueStock(Request $request, Aliment $aliment)
    {
        $this->authorize('view', $aliment);

        $dateDebut = $request->date_debut;
        $dateFin = $request->date_fin;

        $historique = $this->alimentService->historiqueStock($aliment->id, $dateDebut, $dateFin);

        return ApiResponse::success([
            'historique' => $historique,
        ], 'Historique des mouvements de stock récupéré avec succès.');
    }

    /**
     * Obtenir les statistiques d'utilisation d'un aliment.
     */
    public function statistiques(Request $request, Aliment $aliment)
    {
        $this->authorize('view', $aliment);

        $dateDebut = $request->date_debut;
        $dateFin = $request->date_fin;

        $statistiques = $this->alimentService->statistiques($aliment->id, $dateDebut, $dateFin);

        return ApiResponse::success($statistiques, 'Statistiques de l\'aliment récupérées avec succès.');
    }
}
