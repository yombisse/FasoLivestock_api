<?php

namespace App\Services\Admin;

use App\Models\Aliment;
use App\Models\Ration;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class AlimentApiService
{
    /**
     * Lister les aliments avec pagination et filtres.
     */
    public function index(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = Aliment::query()
            ->with(['farm', 'rations'])
            ->when(isset($filters['en_rupture']), fn ($q) =>
                $q->where('stock_actuel', '<=', 0)
            )
            ->when(isset($filters['unite']), fn ($q) =>
                $q->where('unite', $filters['unite'])
            )
            ->orderBy('nom', 'asc');

        return $query->paginate($perPage);
    }

    /**
     * Créer un aliment avec gestion offline-first.
     */
    public function store(array $data, string $userId): Aliment
    {
        // Vérification de conflit offline-first
        if (isset($data['version'])) {
            unset($data['version']);
        }

        $data['sync_status'] = 'synced';
        $data['last_modified_by'] = $userId;
        $data['version'] = 1;

        // Utiliser la ferme courante du contexte si non fournie
        if (!isset($data['farm_id'])) {
            $data['farm_id'] = session('current_farm_id');
        }

        return Aliment::create($data);
    }

    /**
     * Mettre à jour un aliment avec gestion offline-first.
     */
    public function update(Aliment $aliment, array $data, string $userId): Aliment
    {
        // Vérification de conflit offline-first
        if (isset($data['version'])) {
            if ($data['version'] !== $aliment->version) {
                // Conflit détecté
                $aliment->update([
                    'sync_status' => 'conflict',
                ]);
                throw new \Exception('Conflit de version détecté. Veuillez synchroniser vos données.');
            }
        }

        $data['sync_status'] = 'synced';
        $data['last_modified_by'] = $userId;
        $data['version'] = ($aliment->version ?? 1) + 1;

        $aliment->update($data);

        return $aliment->fresh();
    }

    /**
     * Supprimer (soft delete) un aliment.
     */
    public function destroy(Aliment $aliment): bool
    {
        $aliment->update([
            'sync_status' => 'synced',
            'version' => ($aliment->version ?? 1) + 1,
        ]);

        return $aliment->delete();
    }

    /**
     * Restaurer un aliment archivé.
     */
    public function restore(string $id): Aliment
    {
        $aliment = Aliment::onlyTrashed()->findOrFail($id);
        $aliment->restore();

        return $aliment->fresh();
    }

    /**
     * Lister les aliments archivés.
     */
    public function trashed(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = Aliment::onlyTrashed()
            ->with(['farm'])
            ->when(isset($filters['unite']), fn ($q) =>
                $q->where('unite', $filters['unite'])
            )
            ->orderBy('deleted_at', 'desc');

        return $query->paginate($perPage);
    }

    /**
     * Approvisionner le stock d'un aliment.
     */
    public function approvisionner(Aliment $aliment, float $quantite, string $userId, ?string $note = null): Aliment
    {
        return DB::transaction(function () use ($aliment, $quantite, $userId, $note) {
            $aliment->approvisionner($quantite);

            $aliment->update([
                'sync_status' => 'synced',
                'last_modified_by' => $userId,
                'version' => ($aliment->version ?? 1) + 1,
            ]);

            return $aliment->fresh();
        });
    }

    /**
     * Ajuster manuellement le stock d'un aliment.
     */
    public function ajusterStock(Aliment $aliment, float $nouveauStock, string $userId, ?string $note = null): Aliment
    {
        return DB::transaction(function () use ($aliment, $nouveauStock, $userId, $note) {
            $ancienStock = $aliment->stock_actuel;
            $difference = $nouveauStock - $ancienStock;

            if ($difference > 0) {
                $aliment->approvisionner($difference);
            } elseif ($difference < 0) {
                $aliment->deduireStock(abs($difference));
            }

            $aliment->update([
                'sync_status' => 'synced',
                'last_modified_by' => $userId,
                'version' => ($aliment->version ?? 1) + 1,
            ]);

            return $aliment->fresh();
        });
    }

    /**
     * Obtenir les aliments en rupture de stock.
     */
    public function enRupture(): array
    {
        $aliments = Aliment::where('stock_actuel', '<=', 0)
            ->with(['farm'])
            ->orderBy('stock_actuel', 'asc')
            ->get();

        return $aliments->map(fn ($aliment) => [
            'id' => $aliment->id,
            'nom' => $aliment->nom,
            'unite' => $aliment->unite,
            'stock_actuel' => $aliment->stock_actuel,
            'prix_unitaire' => $aliment->prix_unitaire,
            'farm' => [
                'id' => $aliment->farm->id,
                'name' => $aliment->farm->name,
            ],
        ])->toArray();
    }

    /**
     * Obtenir l'historique des mouvements de stock d'un aliment.
     */
    public function historiqueStock(string $alimentId, ?string $dateDebut = null, ?string $dateFin = null): array
    {
        $query = Ration::where('aliment_id', $alimentId)
            ->with(['animal', 'lot', 'farm'])
            ->orderBy('date_distribution', 'desc');

        if ($dateDebut && $dateFin) {
            $query->whereBetween('date_distribution', [$dateDebut, $dateFin]);
        }

        $rations = $query->get();

        return $rations->map(fn ($ration) => [
            'id' => $ration->id,
            'type' => 'distribution',
            'quantite' => $ration->quantite,
            'date' => $ration->date_distribution,
            'heure' => $ration->heure_distribution,
            'cible' => $ration->lot_id ? 'lot' : 'animal',
            'cible_id' => $ration->lot_id ?? $ration->animal_id,
            'cible_nom' => $ration->lot ? $ration->lot->nom_lot : ($ration->animal ? $ration->animal->nom : null),
            'observation' => $ration->observation,
        ])->toArray();
    }

    /**
     * Obtenir les statistiques d'utilisation d'un aliment.
     */
    public function statistiques(string $alimentId, ?string $dateDebut = null, ?string $dateFin = null): array
    {
        $query = Ration::where('aliment_id', $alimentId);

        if ($dateDebut && $dateFin) {
            $query->whereBetween('date_distribution', [$dateDebut, $dateFin]);
        }

        $rations = $query->get();

        $totalDistribue = $rations->sum('quantite');
        $totalCout = $rations->sum(fn ($ration) => $ration->cout_total);
        $nombreDistributions = $rations->count();

        return [
            'total_distribue' => $totalDistribue,
            'total_cout' => $totalCout,
            'nombre_distributions' => $nombreDistributions,
            'moyenne_par_distribution' => $nombreDistributions > 0 ? $totalDistribue / $nombreDistributions : 0,
        ];
    }

    /**
     * Formater un aliment pour la réponse API.
     */
    public function formatAliment(Aliment $aliment): array
    {
        return [
            'id' => $aliment->id,
            'farm_id' => $aliment->farm_id,
            'nom' => $aliment->nom,
            'unite' => $aliment->unite,
            'prix_unitaire' => $aliment->prix_unitaire,
            'stock_actuel' => $aliment->stock_actuel,
            'description' => $aliment->description,
            'est_en_rupture' => $aliment->est_en_rupture,
            'sync_status' => $aliment->sync_status,
            'version' => $aliment->version,
            'deleted_at' => $aliment->deleted_at,
            'created_at' => $aliment->created_at,
            'updated_at' => $aliment->updated_at,
            // Relations
            'farm' => $aliment->farm ? [
                'id' => $aliment->farm->id,
                'name' => $aliment->farm->name,
            ] : null,
            'nombre_rations' => $aliment->rations->count(),
        ];
    }
}
