<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TypeEvenement;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;

class TypeEvenementController extends Controller
{
    /**
     * Lister tous les types d'événements
     */
    public function index(Request $request)
    {
        $types = TypeEvenement::query()
            ->when($request->farm_id, fn($q) => $q->where('farm_id', $request->farm_id))
            ->when($request->search, fn($q) => $q->where('nom_type', 'like', "%{$request->search}%"))
            ->orderBy('nom_type')
            ->get();

        return ApiResponse::success($types, 'Types d\'événements récupérés avec succès.');
    }

    /**
     * Créer un type d'événement (admin only)
     */
    public function store(Request $request)
    {
        $this->authorize('create', TypeEvenement::class);

        $data = $request->validate([
            'nom_type' => 'required|string|max:255',
            'description' => 'nullable|string',
            'categorie' => 'nullable|in:MOUVEMENT,REPRODUCTION,SANITAIRE,AUTRE',
            'farm_id' => 'nullable|uuid|exists:farms,id',
        ]);

        // Définir la catégorie par défaut à AUTRE si non fournie
        if (!isset($data['categorie'])) {
            $data['categorie'] = 'AUTRE';
        }

        $type = TypeEvenement::create($data);

        return ApiResponse::success($type, 'Type d\'événement créé avec succès.', 201);
    }

    /**
     * Détail d'un type d'événement
     */
    public function show(TypeEvenement $typeEvenement)
    {
        return ApiResponse::success($typeEvenement, 'Type d\'événement récupéré avec succès.');
    }

    /**
     * Modifier un type d'événement (admin only)
     */
    public function update(Request $request, TypeEvenement $typeEvenement)
    {
        $this->authorize('update', $typeEvenement);

        // Bloquer la modification des types système
        if ($typeEvenement->is_system) {
            return ApiResponse::error(null, 'Les types système ne peuvent pas être modifiés.', 403);
        }

        $data = $request->validate([
            'nom_type' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'categorie' => 'nullable|in:MOUVEMENT,REPRODUCTION,SANITAIRE,AUTRE',
            'farm_id' => 'nullable|uuid|exists:farms,id',
        ]);

        $typeEvenement->update($data);

        return ApiResponse::success($typeEvenement->fresh(), 'Type d\'événement mis à jour avec succès.');
    }

    /**
     * Supprimer un type d'événement (admin only)
     */
    public function destroy(TypeEvenement $typeEvenement)
    {
        $this->authorize('delete', $typeEvenement);

        // Bloquer la suppression des types système
        if ($typeEvenement->is_system) {
            return ApiResponse::error(null, 'Les types système ne peuvent pas être supprimés.', 403);
        }

        $typeEvenement->delete();

        return ApiResponse::success(null, 'Type d\'événement supprimé avec succès.');
    }
}
