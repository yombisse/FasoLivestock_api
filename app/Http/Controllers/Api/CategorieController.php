<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Categorie;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;

class CategorieController extends Controller
{
    /**
     * Lister toutes les catégories
     */
    public function index(Request $request)
    {
        $categories = Categorie::query()
            ->when($request->farm_id, fn($q) => $q->where('farm_id', $request->farm_id))
            ->when($request->type, fn($q) => $q->where('type', $request->type))
            ->when($request->search, fn($q) => $q->where('nom_categorie', 'like', "%{$request->search}%"))
            ->orderBy('nom_categorie')
            ->get();

        return ApiResponse::success($categories, 'Catégories récupérées avec succès.');
    }

    /**
     * Créer une catégorie
     */
    public function store(Request $request)
    {
        $this->authorize('create', Categorie::class);

        $data = $request->validate([
            'nom_categorie' => 'required|string|max:255',
            'type' => 'required|in:REVENU,DEPENSE',
            'description' => 'nullable|string',
            'farm_id' => 'nullable|uuid|exists:farms,id',
        ]);

        $data['sync_status'] = 'synced';
        $data['version'] = 1;
        $data['last_modified_by'] = $request->user()->id;

        $categorie = Categorie::create($data);

        return ApiResponse::success($categorie, 'Catégorie créée avec succès.', 201);
    }

    /**
     * Détail d'une catégorie
     */
    public function show(Categorie $categorie)
    {
        return ApiResponse::success($categorie, 'Catégorie récupérée avec succès.');
    }

    /**
     * Modifier une catégorie
     */
    public function update(Request $request, Categorie $categorie)
    {
        $this->authorize('update', $categorie);

        $data = $request->validate([
            'nom_categorie' => 'sometimes|string|max:255',
            'type' => 'sometimes|in:REVENU,DEPENSE',
            'description' => 'nullable|string',
            'farm_id' => 'nullable|uuid|exists:farms,id',
        ]);

        $data['sync_status'] = 'synced';
        $data['last_modified_by'] = $request->user()->id;
        $data['version'] = ($categorie->version ?? 1) + 1;

        $categorie->update($data);

        return ApiResponse::success($categorie->fresh(), 'Catégorie mise à jour avec succès.');
    }

    /**
     * Supprimer une catégorie
     */
    public function destroy(Categorie $categorie)
    {
        $this->authorize('delete', $categorie);

        $categorie->update([
            'sync_status' => 'synced',
            'last_modified_by' => auth()->id(),
            'version' => ($categorie->version ?? 1) + 1,
        ]);

        $categorie->delete();

        return ApiResponse::success(null, 'Catégorie supprimée avec succès.');
    }

    /**
     * Liste des catégories supprimées
     */
    public function trashed()
    {
        $categories = Categorie::onlyTrashed()
            ->orderBy('deleted_at', 'desc')
            ->get();

        return ApiResponse::success($categories, 'Catégories supprimées récupérées avec succès.');
    }

    /**
     * Restaurer une catégorie supprimée
     */
    public function restore($id)
    {
        $categorie = Categorie::onlyTrashed()->findOrFail($id);
        
        $this->authorize('update', $categorie);

        $categorie->restore();

        return ApiResponse::success($categorie->fresh(), 'Catégorie restaurée avec succès.');
    }
}
