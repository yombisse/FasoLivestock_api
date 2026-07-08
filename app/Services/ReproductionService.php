<?php

namespace App\Services;

use App\Models\Animal;
use App\Models\Naissance;
use App\Models\Evenement;
use App\Models\TypeEvenement;

class ReproductionService
{
    /**
     * Obtenir le dashboard de reproduction pour une ferme.
     */
    public function dashboard(string $farmId): array
    {
        $femelles = Animal::where('farm_id', $farmId)
            ->where('sexe', 'femelle')
            ->where('statut', 'ACTIF')
            ->count();

        $naissancesEnCours = Naissance::where('farm_id', $farmId)
            ->whereNotNull('date_saillie')
            ->whereNull('date_naissance')
            ->count();

        $misesBasAVenir = Naissance::where('farm_id', $farmId)
            ->whereNotNull('date_mise_bas_prevue')
            ->where('date_mise_bas_prevue', '>=', now())
            ->where('date_mise_bas_prevue', '<=', now()->addDays(7))
            ->count();

        $naissancesMois = Naissance::where('farm_id', $farmId)
            ->whereMonth('date_naissance', now()->month)
            ->whereYear('date_naissance', now()->year)
            ->count();

        $chaleursMois = Evenement::where('farm_id', $farmId)
            ->whereHas('type', fn ($q) => $q->where('nom_type', 'CHALEUR'))
            ->whereMonth('date_evenement', now()->month)
            ->whereYear('date_evenement', now()->year)
            ->count();

        $sailliesMois = Evenement::where('farm_id', $farmId)
            ->whereHas('type', fn ($q) => $q->where('nom_type', 'SAILLIE'))
            ->whereMonth('date_evenement', now()->month)
            ->whereYear('date_evenement', now()->year)
            ->count();

        return [
            'femelles_actives' => $femelles,
            'naissances_en_cours' => $naissancesEnCours,
            'mises_bas_a_venir_7j' => $misesBasAVenir,
            'naissances_ce_mois' => $naissancesMois,
            'chaleurs_ce_mois' => $chaleursMois,
            'saillies_ce_mois' => $sailliesMois,
        ];
    }

    /**
     * Obtenir les prévisions de reproduction.
     */
    public function forecast(string $farmId, int $jours = 30): array
    {
        $misesBas = Naissance::where('farm_id', $farmId)
            ->with(['mother', 'mother.espece'])
            ->whereNotNull('date_mise_bas_prevue')
            ->whereBetween('date_mise_bas_prevue', [
                now(),
                now()->addDays($jours)
            ])
            ->orderBy('date_mise_bas_prevue')
            ->get()
            ->map(fn ($naissance) => [
                'id' => $naissance->id,
                'date_mise_bas_prevue' => $naissance->date_mise_bas_prevue,
                'jours_restants' => now()->diffInDays($naissance->date_mise_bas_prevue, false),
                'urgence' => $this->determinerUrgence($naissance->date_mise_bas_prevue),
                'mother' => [
                    'id' => $naissance->mother->id,
                    'nom' => $naissance->mother->nom,
                    'espece' => $naissance->mother->espece->nom ?? null,
                ],
            ]);

        $femellesEnAge = Animal::where('farm_id', $farmId)
            ->where('sexe', 'femelle')
            ->where('statut', 'ACTIF')
            ->with('espece.parametre')
            ->get()
            ->filter(fn ($animal) =>
                $animal->espece &&
                $animal->espece->parametre &&
                $animal->espece->parametre->estEnAgeDeReproduire($animal->date_naissance)
            )
            ->map(fn ($animal) => [
                'id' => $animal->id,
                'nom' => $animal->nom,
                'espece' => $animal->espece->nom,
                'age_mois' => $animal->date_naissance ? now()->diffInMonths($animal->date_naissance) : null,
            ]);

        return [
            'mises_bas_prevues' => $misesBas,
            'femelles_en_age_reproduction' => $femellesEnAge,
        ];
    }

    /**
     * Obtenir l'historique reproductif d'un animal.
     */
    public function historiqueAnimal(string $animalId): array
    {
        $evenements = Evenement::where('animal_id', $animalId)
            ->with(['type', 'farm'])
            ->whereHas('type', fn ($q) =>
                $q->whereIn('nom_type', ['CHALEUR', 'SAILLIE', 'GESTATION', 'MISE_BAS'])
            )
            ->orderBy('date_evenement', 'desc')
            ->get()
            ->map(fn ($evenement) => [
                'id' => $evenement->id,
                'type' => $evenement->type->nom_type,
                'date_evenement' => $evenement->date_evenement,
                'description' => $evenement->description,
                'cout' => $evenement->cout,
            ]);

        $naissances = Naissance::where('mother_id', $animalId)
            ->with(['petits'])
            ->orderBy('date_naissance', 'desc')
            ->get()
            ->map(fn ($naissance) => [
                'id' => $naissance->id,
                'date_naissance' => $naissance->date_naissance,
                'nombre_petits' => $naissance->nombre_petits,
                'nombre_petits_enregistres' => $naissance->nombre_enregistres,
                'petits' => $naissance->petits->map(fn ($petit) => [
                    'id' => $petit->id,
                    'nom' => $petit->nom,
                    'sexe' => $petit->sexe,
                    'statut' => $petit->statut,
                ]),
            ]);

        return [
            'evenements_reproductifs' => $evenements,
            'naissances' => $naissances,
        ];
    }

    /**
     * Obtenir les statistiques reproductives d'un animal.
     */
    public function statistiquesAnimal(string $animalId): array
    {
        $animal = Animal::with(['espece.parametre'])->findOrFail($animalId);

        $totalChaleurs = Evenement::where('animal_id', $animalId)
            ->whereHas('type', fn ($q) => $q->where('nom_type', 'CHALEUR'))
            ->count();

        $totalSaillies = Evenement::where('animal_id', $animalId)
            ->whereHas('type', fn ($q) => $q->where('nom_type', 'SAILLIE'))
            ->count();

        $totalNaissances = Naissance::where('mother_id', $animalId)->count();

        $totalPetits = Naissance::where('mother_id', $animalId)
            ->sum('nombre_petits');

        $derniereSaillie = Evenement::where('animal_id', $animalId)
            ->whereHas('type', fn ($q) => $q->where('nom_type', 'SAILLIE'))
            ->orderBy('date_evenement', 'desc')
            ->first();

        $derniereNaissance = Naissance::where('mother_id', $animalId)
            ->orderBy('date_naissance', 'desc')
            ->first();

        $enAgeReproduction = $animal->espece && $animal->espece->parametre
            ? $animal->espece->parametre->estEnAgeDeReproduire($animal->date_naissance)
            : false;

        return [
            'total_chaleurs' => $totalChaleurs,
            'total_saillies' => $totalSaillies,
            'total_naissances' => $totalNaissances,
            'total_petits' => $totalPetits,
            'derniere_saillie' => $derniereSaillie ? [
                'date' => $derniereSaillie->date_evenement,
                'description' => $derniereSaillie->description,
            ] : null,
            'derniere_naissance' => $derniereNaissance ? [
                'date' => $derniereNaissance->date_naissance,
                'nombre_petits' => $derniereNaissance->nombre_petits,
            ] : null,
            'en_age_reproduction' => $enAgeReproduction,
        ];
    }

    /**
     * Déterminer le niveau d'urgence pour une mise bas.
     */
    private function determinerUrgence($dateMiseBas): string
    {
        $joursRestants = now()->diffInDays($dateMiseBas, false);

        if ($joursRestants <= 0) {
            return 'retard';
        } elseif ($joursRestants <= 3) {
            return 'critique';
        } elseif ($joursRestants <= 7) {
            return 'haute';
        } elseif ($joursRestants <= 14) {
            return 'moyenne';
        } else {
            return 'normale';
        }
    }
}
