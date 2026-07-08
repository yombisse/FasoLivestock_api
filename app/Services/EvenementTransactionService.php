<?php

namespace App\Services;

use App\Models\Evenement;
use App\Models\Transaction;
use App\Models\Categorie;
use Illuminate\Support\Facades\DB;

class EvenementTransactionService
{
    /**
     * Crée automatiquement une Transaction SORTIE liée à un Evenement 
     * ayant un coût, si ce coût est strictement positif. Idempotent : 
     * ne crée pas de doublon si une Transaction existe déjà pour cet 
     * evenement_id.
     */
    public function creerTransactionDepuisEvenement(Evenement $evenement, float $cout, string $libelleCategorieDefaut): ?Transaction
    {
        if ($cout <= 0) {
            return null;
        }

        // Idempotence : vérifier qu'aucune transaction n'existe déjà pour cet évènement
        $existante = Transaction::where('evenement_id', $evenement->id)->first();
        if ($existante) {
            return $existante;
        }

        $categorie = Categorie::firstOrCreate(
            ['nom_categorie' => $libelleCategorieDefaut, 'farm_id' => null],
            [
                'type' => 'charge',
                'description' => "Frais liés aux événements de type {$libelleCategorieDefaut}",
                'sync_status' => 'synced',
                'version' => 1,
            ]
        );

        return DB::transaction(function () use ($evenement, $cout, $categorie) {
            return Transaction::create([
                'farm_id' => $evenement->farm_id,
                'animal_id' => $evenement->animal_id,
                'evenement_id' => $evenement->id,
                'type_transaction' => 'SORTIE',
                'montant' => $cout,
                'categorie_id' => $categorie->id,
                'date_transaction' => $evenement->date_evenement,
                'description' => "Frais généré automatiquement : {$categorie->nom_categorie}",
                'user_id' => auth()->id(),
                'sync_status' => 'synced',
                'version' => 1,
            ]);
        });
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
