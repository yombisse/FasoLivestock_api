<?php

namespace App\Services\Admin;

use App\Models\Ration;
use App\Models\Aliment;
use App\Models\Animal;
use App\Models\Lot;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class RationApiService
{
    /**
     * Lister les rations avec pagination et filtres.
     */
    public function index(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = Ration::query()
            ->with(['farm', 'aliment', 'animal', 'lot'])
            ->when(isset($filters['aliment_id']), fn ($q) =>
                $q->where('aliment_id', $filters['aliment_id'])
            )
            ->when(isset($filters['animal_id']), fn ($q) =>
                $q->where('animal_id', $filters['animal_id'])
            )
            ->when(isset($filters['lot_id']), fn ($q) =>
                $q->where('lot_id', $filters['lot_id'])
            )
            ->when(isset($filters['date_debut']) && isset($filters['date_fin']), fn ($q) =>
                $q->whereBetween('date_distribution', [$filters['date_debut'], $filters['date_fin']])
            )
            ->orderBy('date_distribution', 'desc')
            ->orderBy('heure_distribution', 'desc');

        return $query->paginate($perPage);
    }

    /**
     * Créer une ration avec gestion offline-first et validation du stock.
     */
    public function store(array $data, string $userId): Ration
    {
        return DB::transaction(function () use ($data, $userId) {
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

            // Vérifier le stock disponible
            $aliment = Aliment::findOrFail($data['aliment_id']);
            if ($aliment->stock_actuel < $data['quantite']) {
                throw new \Exception('Stock insuffisant. Stock disponible: ' . $aliment->stock_actuel . ' ' . $aliment->unite);
            }

            $ration = Ration::create($data);

            // La déduction du stock est automatique via l'observer dans le modèle Ration

            return $ration->fresh();
        });
    }

    /**
     * Mettre à jour une ration avec gestion offline-first.
     */
    public function update(Ration $ration, array $data, string $userId): Ration
    {
        return DB::transaction(function () use ($ration, $data, $userId) {
            // Vérification de conflit offline-first
            if (isset($data['version'])) {
                if ($data['version'] !== $ration->version) {
                    // Conflit détecté
                    $ration->update([
                        'sync_status' => 'conflict',
                    ]);
                    throw new \Exception('Conflit de version détecté. Veuillez synchroniser vos données.');
                }
            }

            $data['sync_status'] = 'synced';
            $data['last_modified_by'] = $userId;
            $data['version'] = ($ration->version ?? 1) + 1;

            // Si la quantité change, ajuster le stock
            if (isset($data['quantite']) && $data['quantite'] !== $ration->quantite) {
                $difference = $data['quantite'] - $ration->quantite;
                $aliment = $ration->aliment;

                if ($difference > 0) {
                    // Ajout de quantité : vérifier le stock
                    if ($aliment->stock_actuel < $difference) {
                        throw new \Exception('Stock insuffisant. Stock disponible: ' . $aliment->stock_actuel . ' ' . $aliment->unite);
                    }
                    $aliment->deduireStock($difference);
                } elseif ($difference < 0) {
                    // Réduction de quantité : remettre en stock
                    $aliment->approvisionner(abs($difference));
                }
            }

            $ration->update($data);

            return $ration->fresh();
        });
    }

    /**
     * Supprimer (soft delete) une ration.
     */
    public function destroy(Ration $ration): bool
    {
        $ration->update([
            'sync_status' => 'synced',
            'version' => ($ration->version ?? 1) + 1,
        ]);

        return $ration->delete();
    }

    /**
     * Restaurer une ration archivée.
     */
    public function restore(string $id): Ration
    {
        $ration = Ration::onlyTrashed()->findOrFail($id);
        $ration->restore();

        return $ration->fresh();
    }

    /**
     * Lister les rations archivées.
     */
    public function trashed(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = Ration::onlyTrashed()
            ->with(['farm', 'aliment', 'animal', 'lot'])
            ->when(isset($filters['aliment_id']), fn ($q) =>
                $q->where('aliment_id', $filters['aliment_id'])
            )
            ->when(isset($filters['animal_id']), fn ($q) =>
                $q->where('animal_id', $filters['animal_id'])
            )
            ->when(isset($filters['lot_id']), fn ($q) =>
                $q->where('lot_id', $filters['lot_id'])
            )
            ->orderBy('deleted_at', 'desc');

        return $query->paginate($perPage);
    }

    /**
     * Distribuer une ration à un lot.
     */
    public function distribuerLot(array $data, string $userId): array
    {
        return DB::transaction(function () use ($data, $userId) {
            $lot = Lot::findOrFail($data['lot_id']);
            $aliment = Aliment::findOrFail($data['aliment_id']);

            // Calculer la quantité totale requise
            $quantiteParAnimal = $data['quantite_par_animal'];
            $nombreAnimaux = $lot->nombre ?? $lot->animals()->count();
            $quantiteTotale = $quantiteParAnimal * $nombreAnimaux;

            // Vérifier le stock disponible
            if ($aliment->stock_actuel < $quantiteTotale) {
                throw new \Exception('Stock insuffisant. Stock disponible: ' . $aliment->stock_actuel . ' ' . $aliment->unite . ', requis: ' . $quantiteTotale);
            }

            // Créer une ration pour le lot
            $ration = Ration::create([
                'farm_id' => $lot->farm_id,
                'aliment_id' => $data['aliment_id'],
                'lot_id' => $data['lot_id'],
                'quantite' => $quantiteTotale,
                'date_distribution' => $data['date_distribution'] ?? now(),
                'heure_distribution' => $data['heure_distribution'] ?? now(),
                'observation' => $data['observation'] ?? null,
                'sync_status' => 'synced',
                'last_modified_by' => $userId,
                'version' => 1,
            ]);

            return [
                'ration' => $ration->fresh(),
                'nombre_animaux' => $nombreAnimaux,
                'quantite_par_animal' => $quantiteParAnimal,
                'quantite_totale' => $quantiteTotale,
            ];
        });
    }

    /**
     * Distribuer une ration à plusieurs animaux.
     */
    public function distribuerAnimaux(array $data, string $userId): array
    {
        return DB::transaction(function () use ($data, $userId) {
            $aliment = Aliment::findOrFail($data['aliment_id']);
            $animalIds = $data['animal_ids'];
            $quantiteParAnimal = $data['quantite_par_animal'];
            $quantiteTotale = $quantiteParAnimal * count($animalIds);

            // Vérifier le stock disponible
            if ($aliment->stock_actuel < $quantiteTotale) {
                throw new \Exception('Stock insuffisant. Stock disponible: ' . $aliment->stock_actuel . ' ' . $aliment->unite . ', requis: ' . $quantiteTotale);
            }

            // Créer une ration pour chaque animal
            $rations = [];
            foreach ($animalIds as $animalId) {
                $animal = Animal::findOrFail($animalId);
                $ration = Ration::create([
                    'farm_id' => $animal->farm_id,
                    'aliment_id' => $data['aliment_id'],
                    'animal_id' => $animalId,
                    'quantite' => $quantiteParAnimal,
                    'date_distribution' => $data['date_distribution'] ?? now(),
                    'heure_distribution' => $data['heure_distribution'] ?? now(),
                    'observation' => $data['observation'] ?? null,
                    'sync_status' => 'synced',
                    'last_modified_by' => $userId,
                    'version' => 1,
                ]);
                $rations[] = $ration->fresh();
            }

            return [
                'rations' => $rations,
                'nombre_animaux' => count($animalIds),
                'quantite_par_animal' => $quantiteParAnimal,
                'quantite_totale' => $quantiteTotale,
            ];
        });
    }

    /**
     * Obtenir l'historique alimentaire d'un animal.
     */
    public function historiqueAnimal(string $animalId, ?string $dateDebut = null, ?string $dateFin = null): array
    {
        $query = Ration::where('animal_id', $animalId)
            ->with(['aliment', 'farm'])
            ->orderBy('date_distribution', 'desc')
            ->orderBy('heure_distribution', 'desc');

        if ($dateDebut && $dateFin) {
            $query->whereBetween('date_distribution', [$dateDebut, $dateFin]);
        }

        $rations = $query->get();

        return [
            'animal_id' => $animalId,
            'rations' => $rations->map(fn ($ration) => [
                'id' => $ration->id,
                'aliment' => [
                    'id' => $ration->aliment->id,
                    'nom' => $ration->aliment->nom,
                    'unite' => $ration->aliment->unite,
                ],
                'quantite' => $ration->quantite,
                'date_distribution' => $ration->date_distribution,
                'heure_distribution' => $ration->heure_distribution,
                'cout_total' => $ration->cout_total,
                'observation' => $ration->observation,
            ]),
            'total_quantite' => $rations->sum('quantite'),
            'total_cout' => $rations->sum(fn ($r) => $r->cout_total),
        ];
    }

    /**
     * Obtenir l'historique alimentaire d'un lot.
     */
    public function historiqueLot(string $lotId, ?string $dateDebut = null, ?string $dateFin = null): array
    {
        $query = Ration::where('lot_id', $lotId)
            ->with(['aliment', 'farm'])
            ->orderBy('date_distribution', 'desc')
            ->orderBy('heure_distribution', 'desc');

        if ($dateDebut && $dateFin) {
            $query->whereBetween('date_distribution', [$dateDebut, $dateFin]);
        }

        $rations = $query->get();

        return [
            'lot_id' => $lotId,
            'rations' => $rations->map(fn ($ration) => [
                'id' => $ration->id,
                'aliment' => [
                    'id' => $ration->aliment->id,
                    'nom' => $ration->aliment->nom,
                    'unite' => $ration->aliment->unite,
                ],
                'quantite' => $ration->quantite,
                'date_distribution' => $ration->date_distribution,
                'heure_distribution' => $ration->heure_distribution,
                'cout_total' => $ration->cout_total,
                'observation' => $ration->observation,
            ]),
            'total_quantite' => $rations->sum('quantite'),
            'total_cout' => $rations->sum(fn ($r) => $r->cout_total),
        ];
    }

    /**
     * Obtenir la consommation totale d'un animal.
     */
    public function consommationAnimal(string $animalId, ?string $dateDebut = null, ?string $dateFin = null): array
    {
        $query = Ration::where('animal_id', $animalId);

        if ($dateDebut && $dateFin) {
            $query->whereBetween('date_distribution', [$dateDebut, $dateFin]);
        }

        $rations = $query->get();

        $consommationParAliment = $rations->groupBy('aliment_id')
            ->map(fn ($group) => [
                'aliment_id' => $group->first()->aliment_id,
                'aliment_nom' => $group->first()->aliment->nom,
                'unite' => $group->first()->aliment->unite,
                'total_quantite' => $group->sum('quantite'),
                'total_cout' => $group->sum(fn ($r) => $r->cout_total),
                'nombre_distributions' => $group->count(),
            ])
            ->values();

        return [
            'animal_id' => $animalId,
            'consommation_par_aliment' => $consommationParAliment,
            'total_quantite' => $rations->sum('quantite'),
            'total_cout' => $rations->sum(fn ($r) => $r->cout_total),
        ];
    }

    /**
     * Obtenir la consommation totale d'un lot.
     */
    public function consommationLot(string $lotId, ?string $dateDebut = null, ?string $dateFin = null): array
    {
        $query = Ration::where('lot_id', $lotId);

        if ($dateDebut && $dateFin) {
            $query->whereBetween('date_distribution', [$dateDebut, $dateFin]);
        }

        $rations = $query->get();

        $consommationParAliment = $rations->groupBy('aliment_id')
            ->map(fn ($group) => [
                'aliment_id' => $group->first()->aliment_id,
                'aliment_nom' => $group->first()->aliment->nom,
                'unite' => $group->first()->aliment->unite,
                'total_quantite' => $group->sum('quantite'),
                'total_cout' => $group->sum(fn ($r) => $r->cout_total),
                'nombre_distributions' => $group->count(),
            ])
            ->values();

        return [
            'lot_id' => $lotId,
            'consommation_par_aliment' => $consommationParAliment,
            'total_quantite' => $rations->sum('quantite'),
            'total_cout' => $rations->sum(fn ($r) => $r->cout_total),
        ];
    }

    /**
     * Obtenir les statistiques globales d'alimentation pour une ferme.
     */
    public function statistiquesGlobales(string $farmId, ?string $dateDebut = null, ?string $dateFin = null): array
    {
        $query = Ration::where('farm_id', $farmId);

        if ($dateDebut && $dateFin) {
            $query->whereBetween('date_distribution', [$dateDebut, $dateFin]);
        }

        $rations = $query->get();

        $statistiquesParAliment = $rations->groupBy('aliment_id')
            ->map(fn ($group) => [
                'aliment_id' => $group->first()->aliment_id,
                'aliment_nom' => $group->first()->aliment->nom,
                'unite' => $group->first()->aliment->unite,
                'total_quantite' => $group->sum('quantite'),
                'total_cout' => $group->sum(fn ($r) => $r->cout_total),
                'nombre_distributions' => $group->count(),
            ])
            ->values();

        return [
            'farm_id' => $farmId,
            'statistiques_par_aliment' => $statistiquesParAliment,
            'total_quantite' => $rations->sum('quantite'),
            'total_cout' => $rations->sum(fn ($r) => $r->cout_total),
            'nombre_distributions' => $rations->count(),
        ];
    }

    /**
     * Formater une ration pour la réponse API.
     */
    public function formatRation(Ration $ration): array
    {
        return [
            'id' => $ration->id,
            'farm_id' => $ration->farm_id,
            'aliment_id' => $ration->aliment_id,
            'animal_id' => $ration->animal_id,
            'lot_id' => $ration->lot_id,
            'quantite' => $ration->quantite,
            'date_distribution' => $ration->date_distribution,
            'heure_distribution' => $ration->heure_distribution,
            'observation' => $ration->observation,
            'sync_status' => $ration->sync_status,
            'version' => $ration->version,
            'deleted_at' => $ration->deleted_at,
            'created_at' => $ration->created_at,
            'updated_at' => $ration->updated_at,
            // Relations
            'farm' => $ration->farm ? [
                'id' => $ration->farm->id,
                'name' => $ration->farm->name,
            ] : null,
            'aliment' => $ration->aliment ? [
                'id' => $ration->aliment->id,
                'nom' => $ration->aliment->nom,
                'unite' => $ration->aliment->unite,
                'prix_unitaire' => $ration->aliment->prix_unitaire,
            ] : null,
            'animal' => $ration->animal ? [
                'id' => $ration->animal->id,
                'nom' => $ration->animal->nom,
                'sexe' => $ration->animal->sexe,
            ] : null,
            'lot' => $ration->lot ? [
                'id' => $ration->lot->id,
                'nom_lot' => $ration->lot->nom_lot,
            ] : null,
            'est_pour_lot' => $ration->est_pour_lot,
            'cout_total' => $ration->cout_total,
        ];
    }
}
