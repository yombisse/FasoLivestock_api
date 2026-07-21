<?php

namespace App\Services;

use App\Models\Evenement;
use App\Models\Transaction;
use App\Models\Categorie;
use App\Services\Sync\TransactionDeduplicationService;
use Illuminate\Support\Facades\DB;

class EvenementTransactionService
{
    protected TransactionDeduplicationService $dedupService;

    public function __construct(TransactionDeduplicationService $dedupService)
    {
        $this->dedupService = $dedupService;
    }

    /**
     * Crée automatiquement une Transaction SORTIE liée à un Evenement 
     * ayant un coût. Idempotent : ne crée pas de doublon si une Transaction 
     * existe déjà pour cet evenement_id. Le coût peut être 0.
     */
    public function creerTransactionDepuisEvenement(Evenement $evenement, float $cout, string $libelleCategorieDefaut, ?string $userId = null): ?Transaction
    {
        // Créer la transaction même si le coût est 0

        // Idempotence améliorée : vérifier via business key (animal_id + evenement_id + date + type)
        $transactionData = [
            'animal_id' => $evenement->animal_id,
            'evenement_id' => $evenement->id,
            'date_transaction' => $evenement->date_evenement,
            'type_transaction' => 'SORTIE',
        ];

        $existante = $this->dedupService->findDuplicate($transactionData);
        if ($existante) {
            // Transaction existe déjà (probablement créée par mobile), fusionner avec priorité backend
            return $this->dedupService->merge($existante, array_merge($transactionData, [
                'montant' => $cout,
                'version' => $existante->version,
            ]));
        }

        $categorie = Categorie::firstOrCreate(
            ['nom_categorie' => $libelleCategorieDefaut, 'farm_id' => null],
            [
                'type' => 'DEPENSE',
                'description' => "Frais liés aux événements de type {$libelleCategorieDefaut}",
                'sync_status' => 'synced',
                'version' => 1,
            ]
        );

        // NE PAS créer de transaction ici car cette méthode est déjà appelée
        // depuis une transaction dans SanteEvenementService::store()
        // La génération du numéro utilise lockForUpdate() qui fonctionne dans la transaction parente

        // Générer le numéro de transaction
        $numeroTransaction = Transaction::generateNumero();

        return Transaction::create([
            'farm_id' => $evenement->farm_id,
            'animal_id' => $evenement->animal_id,
            'evenement_id' => $evenement->id,
            'type_transaction' => 'SORTIE',
            'montant' => $cout,
            'categorie_id' => $categorie->id,
            'date_transaction' => $evenement->date_evenement,
            'description' => "Frais généré automatiquement : {$categorie->nom_categorie}",
            'user_id' => $userId ?? auth()->id(),
            'sync_status' => 'synced',
            'version' => 1,
            'numero_transaction' => $numeroTransaction,
        ]);
    }

    /**
     * Met à jour la Transaction associée si le coût de l'événement change, 
     * ou la crée si elle n'existait pas encore (cas d'un coût ajouté après coup).
     */
    public function synchroniserTransactionDepuisEvenement(Evenement $evenement, float $nouveauCout, string $libelleCategorieDefaut): ?Transaction
    {
        $existante = Transaction::where('evenement_id', $evenement->id)->first();

        if ($nouveauCout <= 0) {
            // Coût retiré : soft-delete la transaction si elle existait
            if ($existante) {
                $existante->delete();
            }
            return null;
        }

        if ($existante) {
            $existante->update([
                'montant' => $nouveauCout,
                'date_transaction' => $evenement->date_evenement,
                'version' => $existante->version + 1,
            ]);
            return $existante;
        }

        return $this->creerTransactionDepuisEvenement($evenement, $nouveauCout, $libelleCategorieDefaut);
    }

    /**
     * Soft-delete la Transaction associée à un événement lors de sa suppression.
     */
    public function supprimerTransactionDepuisEvenement(Evenement $evenement): void
    {
        $transaction = Transaction::where('evenement_id', $evenement->id)->first();
        if ($transaction) {
            $transaction->delete();
        }
    }
}
