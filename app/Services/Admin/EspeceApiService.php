<?php

namespace App\Services\Admin;

use App\Models\Espece;
use App\Models\EspeceParametre;
use Illuminate\Pagination\LengthAwarePaginator;

class EspeceApiService
{
    /**
     * Lister les espèces avec pagination et recherche.
     */
    public function index(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = Espece::query()
            ->with('parametre')
            ->when(isset($filters['search']), fn ($q) =>
                $q->where('nom', 'like', "%{$filters['search']}%")
            )
            ->orderBy('nom', 'asc');

        return $query->paginate($perPage);
    }

    /**
     * Créer une espèce.
     */
    public function store(array $data): Espece
    {
        return Espece::create($data);
    }

    /**
     * Mettre à jour une espèce.
     */
    public function update(Espece $espece, array $data): Espece
    {
        $espece->update($data);

        return $espece->fresh();
    }

    /**
     * Supprimer (soft delete) une espèce.
     */
    public function destroy(Espece $espece): bool
    {
        return $espece->delete();
    }

    /**
     * Restaurer une espèce archivée.
     */
    public function restore(string $id): Espece
    {
        $espece = Espece::onlyTrashed()->findOrFail($id);
        $espece->restore();

        return $espece->fresh();
    }

    /**
     * Lister les espèces archivées.
     */
    public function trashed(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = Espece::onlyTrashed()
            ->with('parametre')
            ->when(isset($filters['search']), fn ($q) =>
                $q->where('nom', 'like', "%{$filters['search']}%")
            )
            ->orderBy('deleted_at', 'desc');

        return $query->paginate($perPage);
    }

    /**
     * Afficher les paramètres d'une espèce.
     */
    public function showParametres(Espece $espece): ?EspeceParametre
    {
        return $espece->parametre;
    }

    /**
     * Créer ou mettre à jour les paramètres d'une espèce.
     */
    public function updateParametres(Espece $espece, array $data): EspeceParametre
    {
        return EspeceParametre::updateOrCreate(
            ['espece_id' => $espece->id],
            $data
        );
    }

    /**
     * Formater une espèce pour la réponse API.
     */
    public function formatEspece(Espece $espece): array
    {
        return [
            'id' => $espece->id,
            'nom' => $espece->nom,
            'description' => $espece->description,
            'deleted_at' => $espece->deleted_at,
            'created_at' => $espece->created_at,
            'updated_at' => $espece->updated_at,
            // Relations
            'parametre' => $espece->parametre ? [
                'id' => $espece->parametre->id,
                'espece_id' => $espece->parametre->espece_id,
                'duree_gestation_jours' => $espece->parametre->duree_gestation_jours,
                'age_reproduction_mois' => $espece->parametre->age_reproduction_mois,
                'nombre_petits_typique' => $espece->parametre->nombre_petits_typique,
                'intervalle_vaccin_jours' => $espece->parametre->intervalle_vaccin_jours,
                'age_sevrage_jours' => $espece->parametre->age_sevrage_jours,
                'poids_naissance_moyen_kg' => $espece->parametre->poids_naissance_moyen_kg,
                'poids_adulte_moyen_kg' => $espece->parametre->poids_adulte_moyen_kg,
            ] : null,
        ];
    }

    /**
     * Formater les paramètres pour la réponse API.
     */
    public function formatParametres(EspeceParametre $parametre): array
    {
        return [
            'id' => $parametre->id,
            'espece_id' => $parametre->espece_id,
            'duree_gestation_jours' => $parametre->duree_gestation_jours,
            'age_reproduction_mois' => $parametre->age_reproduction_mois,
            'nombre_petits_typique' => $parametre->nombre_petits_typique,
            'intervalle_vaccin_jours' => $parametre->intervalle_vaccin_jours,
            'age_sevrage_jours' => $parametre->age_sevrage_jours,
            'poids_naissance_moyen_kg' => $parametre->poids_naissance_moyen_kg,
            'poids_adulte_moyen_kg' => $parametre->poids_adulte_moyen_kg,
            'created_at' => $parametre->created_at,
            'updated_at' => $parametre->updated_at,
        ];
    }
}
