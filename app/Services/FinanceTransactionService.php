<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\Animal;
use App\Models\Evenement;
use App\Models\TypeEvenement;
use App\Models\Categorie;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class FinanceTransactionService
{
    /**
     * Lister les transactions avec pagination et filtres.
     */
    public function index(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = Transaction::query()
            ->with(['farm', 'user', 'animal', 'categorie', 'evenement'])
            ->when(isset($filters['type_transaction']), fn ($q) =>
                $q->where('type_transaction', $filters['type_transaction'])
            )
            ->when(isset($filters['animal_id']), fn ($q) =>
                $q->where('animal_id', $filters['animal_id'])
            )
            ->when(isset($filters['categorie_id']), fn ($q) =>
                $q->where('categorie_id', $filters['categorie_id'])
            )
            ->when(isset($filters['date_debut']) && isset($filters['date_fin']), fn ($q) =>
                $q->whereBetween('date_transaction', [$filters['date_debut'], $filters['date_fin']])
            )
            ->when(isset($filters['revenus']), fn ($q) =>
                $q->revenus()
            )
            ->when(isset($filters['charges']), fn ($q) =>
                $q->charges()
            )
            ->orderBy('date_transaction', 'desc');

        return $query->paginate($perPage);
    }

    /**
     * Créer une transaction avec gestion offline-first et cohérence cheptel.
     */
    public function store(array $data, string $userId): Transaction
    {
        return DB::transaction(function () use ($data, $userId) {
            // Vérification de conflit offline-first
            if (isset($data['version'])) {
                unset($data['version']);
            }

            $data['sync_status'] = 'synced';
            $data['last_modified_by'] = $userId;
            $data['version'] = 1;
            $data['user_id'] = $userId;

            // Utiliser la ferme courante du contexte si non fournie
            if (!isset($data['farm_id'])) {
                $data['farm_id'] = session('current_farm_id');
            }

            // Appliquer les règles métier si animal_id présent
            if (isset($data['animal_id'])) {
                $animal = Animal::findOrFail($data['animal_id']);
                $data = $this->appliquerReglesCheptel($data, $animal);
            }

            $transaction = Transaction::create($data);

            return $transaction->fresh();
        });
    }

    /**
     * Mettre à jour une transaction avec gestion offline-first et cohérence cheptel.
     */
    public function update(Transaction $transaction, array $data, string $userId): Transaction
    {
        return DB::transaction(function () use ($transaction, $data, $userId) {
            // Vérification de conflit offline-first
            if (isset($data['version'])) {
                if ($data['version'] !== $transaction->version) {
                    // Conflit détecté
                    $transaction->update([
                        'sync_status' => 'conflict',
                    ]);
                    throw new \Exception('Conflit de version détecté. Veuillez synchroniser vos données.');
                }
            }

            $data['sync_status'] = 'synced';
            $data['last_modified_by'] = $userId;
            $data['version'] = ($transaction->version ?? 1) + 1;

            // Appliquer les règles métier si animal_id change ou est ajouté
            if (isset($data['animal_id']) && $data['animal_id'] !== $transaction->animal_id) {
                $animal = Animal::findOrFail($data['animal_id']);
                $data = $this->appliquerReglesCheptel($data, $animal);
            }

            $transaction->update($data);

            return $transaction->fresh();
        });
    }

    /**
     * Supprimer (soft delete) une transaction.
     */
    public function destroy(Transaction $transaction): bool
    {
        $transaction->update([
            'sync_status' => 'synced',
            'version' => ($transaction->version ?? 1) + 1,
        ]);

        return $transaction->delete();
    }

    /**
     * Restaurer une transaction archivée.
     */
    public function restore(string $id): Transaction
    {
        $transaction = Transaction::onlyTrashed()->findOrFail($id);
        $transaction->restore();

        return $transaction->fresh();
    }

    /**
     * Lister les transactions archivées.
     */
    public function trashed(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = Transaction::onlyTrashed()
            ->with(['farm', 'user', 'animal', 'categorie'])
            ->when(isset($filters['type_transaction']), fn ($q) =>
                $q->where('type_transaction', $filters['type_transaction'])
            )
            ->when(isset($filters['animal_id']), fn ($q) =>
                $q->where('animal_id', $filters['animal_id'])
            )
            ->when(isset($filters['categorie_id']), fn ($q) =>
                $q->where('categorie_id', $filters['categorie_id'])
            )
            ->orderBy('deleted_at', 'desc');

        return $query->paginate($perPage);
    }

    /**
     * Obtenir les revenus avec filtres.
     */
    public function revenus(array $filters): array
    {
        $query = Transaction::query()
            ->revenus()
            ->with(['farm', 'animal', 'categorie'])
            ->when(isset($filters['date_debut']) && isset($filters['date_fin']), fn ($q) =>
                $q->whereBetween('date_transaction', [$filters['date_debut'], $filters['date_fin']])
            )
            ->when(isset($filters['categorie_id']), fn ($q) =>
                $q->where('categorie_id', $filters['categorie_id'])
            )
            ->when(isset($filters['animal_id']), fn ($q) =>
                $q->where('animal_id', $filters['animal_id'])
            );

        $transactions = $query->get();

        return [
            'transactions' => $transactions->map(fn ($t) => $this->formatTransaction($t)),
            'total_revenus' => $transactions->sum('montant'),
            'nombre_transactions' => $transactions->count(),
        ];
    }

    /**
     * Obtenir les revenus par période.
     */
    public function revenusParPeriode(string $dateDebut, string $dateFin): array
    {
        $transactions = Transaction::query()
            ->revenus()
            ->whereBetween('date_transaction', [$dateDebut, $dateFin])
            ->with(['categorie'])
            ->orderBy('date_transaction')
            ->get();

        $revenusParCategorie = $transactions->groupBy('categorie_id')
            ->map(fn ($group) => [
                'categorie_id' => $group->first()->categorie_id,
                'categorie_nom' => $group->first()->categorie->nom_categorie ?? 'Non catégorisé',
                'total' => $group->sum('montant'),
                'nombre' => $group->count(),
            ])
            ->values();

        return [
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin,
            'total_revenus' => $transactions->sum('montant'),
            'nombre_transactions' => $transactions->count(),
            'revenus_par_categorie' => $revenusParCategorie,
        ];
    }

    /**
     * Obtenir les revenus par catégorie.
     */
    public function revenusParCategorie(?string $dateDebut = null, ?string $dateFin = null): array
    {
        $query = Transaction::query()->revenus();

        if ($dateDebut && $dateFin) {
            $query->whereBetween('date_transaction', [$dateDebut, $dateFin]);
        }

        $transactions = $query->get();

        $revenusParCategorie = $transactions->groupBy('categorie_id')
            ->map(fn ($group) => [
                'categorie_id' => $group->first()->categorie_id,
                'categorie_nom' => $group->first()->categorie->nom_categorie ?? 'Non catégorisé',
                'total' => $group->sum('montant'),
                'nombre' => $group->count(),
            ])
            ->values();

        return [
            'revenus_par_categorie' => $revenusParCategorie,
            'total_revenus' => $transactions->sum('montant'),
        ];
    }

    /**
     * Obtenir les revenus par animal.
     */
    public function revenusParAnimal(?string $dateDebut = null, ?string $dateFin = null): array
    {
        $query = Transaction::query()
            ->revenus()
            ->whereNotNull('animal_id');

        if ($dateDebut && $dateFin) {
            $query->whereBetween('date_transaction', [$dateDebut, $dateFin]);
        }

        $transactions = $query->get();

        $revenusParAnimal = $transactions->groupBy('animal_id')
            ->map(fn ($group) => [
                'animal_id' => $group->first()->animal_id,
                'animal_nom' => $group->first()->animal->nom ?? 'Non nommé',
                'total' => $group->sum('montant'),
                'nombre' => $group->count(),
            ])
            ->values();

        return [
            'revenus_par_animal' => $revenusParAnimal,
            'total_revenus' => $transactions->sum('montant'),
        ];
    }

    /**
     * Obtenir les charges avec filtres.
     */
    public function charges(array $filters): array
    {
        $query = Transaction::query()
            ->charges()
            ->with(['farm', 'animal', 'categorie'])
            ->when(isset($filters['date_debut']) && isset($filters['date_fin']), fn ($q) =>
                $q->whereBetween('date_transaction', [$filters['date_debut'], $filters['date_fin']])
            )
            ->when(isset($filters['categorie_id']), fn ($q) =>
                $q->where('categorie_id', $filters['categorie_id'])
            );

        $transactions = $query->get();

        return [
            'transactions' => $transactions->map(fn ($t) => $this->formatTransaction($t)),
            'total_charges' => $transactions->sum('montant'),
            'nombre_transactions' => $transactions->count(),
        ];
    }

    /**
     * Obtenir les charges par période.
     */
    public function chargesParPeriode(string $dateDebut, string $dateFin): array
    {
        $transactions = Transaction::query()
            ->charges()
            ->whereBetween('date_transaction', [$dateDebut, $dateFin])
            ->with(['categorie'])
            ->orderBy('date_transaction')
            ->get();

        $chargesParCategorie = $transactions->groupBy('categorie_id')
            ->map(fn ($group) => [
                'categorie_id' => $group->first()->categorie_id,
                'categorie_nom' => $group->first()->categorie->nom_categorie ?? 'Non catégorisé',
                'total' => $group->sum('montant'),
                'nombre' => $group->count(),
            ])
            ->values();

        return [
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin,
            'total_charges' => $transactions->sum('montant'),
            'nombre_transactions' => $transactions->count(),
            'charges_par_categorie' => $chargesParCategorie,
        ];
    }

    /**
     * Obtenir les charges par catégorie.
     */
    public function chargesParCategorie(?string $dateDebut = null, ?string $dateFin = null): array
    {
        $query = Transaction::query()->charges();

        if ($dateDebut && $dateFin) {
            $query->whereBetween('date_transaction', [$dateDebut, $dateFin]);
        }

        $transactions = $query->get();

        $chargesParCategorie = $transactions->groupBy('categorie_id')
            ->map(fn ($group) => [
                'categorie_id' => $group->first()->categorie_id,
                'categorie_nom' => $group->first()->categorie->nom_categorie ?? 'Non catégorisé',
                'total' => $group->sum('montant'),
                'nombre' => $group->count(),
            ])
            ->values();

        return [
            'charges_par_categorie' => $chargesParCategorie,
            'total_charges' => $transactions->sum('montant'),
        ];
    }

    /**
     * Obtenir le bilan financier.
     */
    public function bilan(?string $dateDebut = null, ?string $dateFin = null): array
    {
        $query = Transaction::query();

        if ($dateDebut && $dateFin) {
            $query->whereBetween('date_transaction', [$dateDebut, $dateFin]);
        }

        $bilan = $query->bilan($dateDebut, $dateFin);

        return [
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin,
            'revenus' => $bilan['revenus'],
            'charges' => $bilan['charges'],
            'benefice' => $bilan['benefice'],
            'marge_beneficiaire' => $bilan['revenus'] > 0 ? round(($bilan['benefice'] / $bilan['revenus']) * 100, 2) : 0,
        ];
    }

    /**
     * Obtenir le bilan par ferme.
     */
    public function bilanParFerme(string $farmId, ?string $dateDebut = null, ?string $dateFin = null): array
    {
        $query = Transaction::where('farm_id', $farmId);

        if ($dateDebut && $dateFin) {
            $query->whereBetween('date_transaction', [$dateDebut, $dateFin]);
        }

        $bilan = $query->bilan($dateDebut, $dateFin);

        return [
            'farm_id' => $farmId,
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin,
            'revenus' => $bilan['revenus'],
            'charges' => $bilan['charges'],
            'benefice' => $bilan['benefice'],
            'marge_beneficiaire' => $bilan['revenus'] > 0 ? round(($bilan['benefice'] / $bilan['revenus']) * 100, 2) : 0,
        ];
    }

    /**
     * Obtenir les statistiques globales financières.
     */
    public function statistiquesGlobales(?string $dateDebut = null, ?string $dateFin = null): array
    {
        $query = Transaction::query();

        if ($dateDebut && $dateFin) {
            $query->whereBetween('date_transaction', [$dateDebut, $dateFin]);
        }

        $transactions = $query->get();

        $revenus = $transactions->where('type_transaction', 'ENTREE');
        $charges = $transactions->where('type_transaction', 'SORTIE');

        return [
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin,
            'total_revenus' => $revenus->sum('montant'),
            'total_charges' => $charges->sum('montant'),
            'benefice' => $revenus->sum('montant') - $charges->sum('montant'),
            'nombre_revenus' => $revenus->count(),
            'nombre_charges' => $charges->count(),
            'moyenne_revenu' => $revenus->count() > 0 ? $revenus->avg('montant') : 0,
            'moyenne_charge' => $charges->count() > 0 ? $charges->avg('montant') : 0,
        ];
    }

    /**
     * Appliquer les règles métier cheptel ↔ finance.
     */
    private function appliquerReglesCheptel(array $data, Animal $animal): array
    {
        // CAS VENTE : type_transaction = ENTREE, categorie = vente
        if (isset($data['type_transaction']) && $data['type_transaction'] === 'ENTREE') {
            // Vérifier si c'est une vente d'animal
            if (!isset($data['categorie_id'])) {
                $categorieVente = Categorie::where('nom_categorie', 'like', '%vente%')
                    ->where('type', 'revenu')
                    ->first();

                if ($categorieVente) {
                    $data['categorie_id'] = $categorieVente->id;
                }
            }

            // Mettre à jour le statut de l'animal
            $animal->update([
                'statut' => 'SORTI',
            ]);

            // Créer un événement de vente si nécessaire
            $typeEvenementVente = TypeEvenement::where('nom_type', 'like', '%vente%')->first();
            if ($typeEvenementVente && !isset($data['evenement_id'])) {
                $evenement = Evenement::create([
                    'farm_id' => $animal->farm_id,
                    'animal_id' => $animal->id,
                    'type_evenement_id' => $typeEvenementVente->id,
                    'date_evenement' => $data['date_transaction'] ?? now(),
                    'description' => 'Vente de l\'animal',
                    'cout' => $data['montant'] ?? 0,
                    'statut_avant' => $animal->statut,
                    'statut_apres' => 'SORTI',
                    'sync_status' => 'synced',
                    'last_modified_by' => $data['last_modified_by'] ?? auth()->id(),
                    'version' => 1,
                ]);
                $data['evenement_id'] = $evenement->id;
            }
        }

        // CAS ACHAT : type_transaction = SORTIE, categorie = achat animal
        if (isset($data['type_transaction']) && $data['type_transaction'] === 'SORTIE') {
            // Vérifier si c'est un achat d'animal
            if (!isset($data['categorie_id'])) {
                $categorieAchat = Categorie::where('nom_categorie', 'like', '%achat%')
                    ->where('type', 'charge')
                    ->first();

                if ($categorieAchat) {
                    $data['categorie_id'] = $categorieAchat->id;
                }
            }

            // Mettre à jour le statut de l'animal
            $animal->update([
                'statut' => 'ACTIF',
            ]);

            // Créer un événement d'achat si nécessaire
            $typeEvenementAchat = TypeEvenement::where('nom_type', 'like', '%achat%')->first();
            if ($typeEvenementAchat && !isset($data['evenement_id'])) {
                $evenement = Evenement::create([
                    'farm_id' => $animal->farm_id,
                    'animal_id' => $animal->id,
                    'type_evenement_id' => $typeEvenementAchat->id,
                    'date_evenement' => $data['date_transaction'] ?? now(),
                    'description' => 'Achat de l\'animal',
                    'cout' => $data['montant'] ?? 0,
                    'statut_avant' => $animal->statut,
                    'statut_apres' => 'ACTIF',
                    'sync_status' => 'synced',
                    'last_modified_by' => $data['last_modified_by'] ?? auth()->id(),
                    'version' => 1,
                ]);
                $data['evenement_id'] = $evenement->id;
            }
        }

        return $data;
    }

    /**
     * Formater une transaction pour la réponse API.
     */
    public function formatTransaction(Transaction $transaction): array
    {
        return [
            'id' => $transaction->id,
            'farm_id' => $transaction->farm_id,
            'type_transaction' => $transaction->type_transaction,
            'montant' => $transaction->montant,
            'montant_signe' => $transaction->montant_signe,
            'date_transaction' => $transaction->date_transaction,
            'user_id' => $transaction->user_id,
            'animal_id' => $transaction->animal_id,
            'categorie_id' => $transaction->categorie_id,
            'description' => $transaction->description,
            'evenement_id' => $transaction->evenement_id,
            'sync_status' => $transaction->sync_status,
            'version' => $transaction->version,
            'deleted_at' => $transaction->deleted_at,
            'created_at' => $transaction->created_at,
            'updated_at' => $transaction->updated_at,
            // Relations
            'farm' => $transaction->farm ? [
                'id' => $transaction->farm->id,
                'name' => $transaction->farm->name,
            ] : null,
            'user' => $transaction->user ? [
                'id' => $transaction->user->id,
                'name' => $transaction->user->name,
            ] : null,
            'animal' => $transaction->animal ? [
                'id' => $transaction->animal->id,
                'nom' => $transaction->animal->nom,
                'statut' => $transaction->animal->statut,
            ] : null,
            'categorie' => $transaction->categorie ? [
                'id' => $transaction->categorie->id,
                'nom_categorie' => $transaction->categorie->nom_categorie,
                'type' => $transaction->categorie->type,
            ] : null,
            'evenement' => $transaction->evenement ? [
                'id' => $transaction->evenement->id,
                'date_evenement' => $transaction->evenement->date_evenement,
                'description' => $transaction->evenement->description,
            ] : null,
            // Accesseurs
            'est_revenu' => $transaction->est_revenu,
            'est_charge' => $transaction->est_charge,
            'a_evenement' => $transaction->a_evenement,
        ];
    }
}
