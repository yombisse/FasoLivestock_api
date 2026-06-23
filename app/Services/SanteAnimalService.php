<?php

namespace App\Services;

use App\Models\Animal;
use App\Models\Evenement;
use App\Models\SanteRappel;
use App\Models\TypeEvenement;

class SanteAnimalService
{
    /**
     * Obtenir l'historique médical complet d'un animal.
     */
    public function historiqueMedical(string $animalId): array
    {
        $animal = Animal::with(['espece'])->findOrFail($animalId);

        // Événements sanitaires
        $evenements = Evenement::where('animal_id', $animalId)
            ->with(['type', 'farm'])
            ->sanitaires()
            ->orderBy('date_evenement', 'desc')
            ->get()
            ->map(fn ($evenement) => [
                'id' => $evenement->id,
                'type' => $evenement->type->nom_type,
                'date_evenement' => $evenement->date_evenement,
                'description' => $evenement->description,
                'cout' => $evenement->cout,
                'created_at' => $evenement->created_at,
            ]);

        // Rappels sanitaires
        $rappels = SanteRappel::where('animal_id', $animalId)
            ->with(['evenement'])
            ->orderBy('date_prevue', 'desc')
            ->get()
            ->map(fn ($rappel) => [
                'id' => $rappel->id,
                'type_rappel' => $rappel->type_rappel,
                'date_prevue' => $rappel->date_prevue,
                'date_realisee' => $rappel->date_realisee,
                'statut' => $rappel->statut,
                'note' => $rappel->note,
                'evenement' => $rappel->evenement ? [
                    'id' => $rappel->evenement->id,
                    'date_evenement' => $rappel->evenement->date_evenement,
                    'description' => $rappel->evenement->description,
                ] : null,
                'created_at' => $rappel->created_at,
            ]);

        return [
            'animal' => [
                'id' => $animal->id,
                'nom' => $animal->nom,
                'sexe' => $animal->sexe,
                'espece' => $animal->espece->nom ?? null,
                'date_naissance' => $animal->date_naissance,
            ],
            'evenements_sanitaires' => $evenements,
            'rappels_sanitaires' => $rappels,
        ];
    }

    /**
     * Obtenir les statistiques sanitaires d'un animal.
     */
    public function statistiquesSanitaires(string $animalId): array
    {
        $animal = Animal::findOrFail($animalId);

        // Événements sanitaires par type
        $vaccinations = Evenement::where('animal_id', $animalId)
            ->whereHas('type', fn ($q) => $q->where('nom_type', 'VACCINATION'))
            ->count();

        $traitements = Evenement::where('animal_id', $animalId)
            ->whereHas('type', fn ($q) => $q->where('nom_type', 'TRAITEMENT'))
            ->count();

        $controles = Evenement::where('animal_id', $animalId)
            ->whereHas('type', fn ($q) => $q->where('nom_type', 'CONTROLE'))
            ->count();

        // Rappels sanitaires
        $rappelsEnAttente = SanteRappel::where('animal_id', $animalId)
            ->where('statut', 'EN_ATTENTE')
            ->count();

        $rappelsEnRetard = SanteRappel::where('animal_id', $animalId)
            ->where('statut', 'EN_RETARD')
            ->count();

        $rappelsRealises = SanteRappel::where('animal_id', $animalId)
            ->where('statut', 'REALISE')
            ->count();

        // Dernier événement sanitaire
        $dernierEvenement = Evenement::where('animal_id', $animalId)
            ->sanitaires()
            ->orderBy('date_evenement', 'desc')
            ->first();

        // Prochain rappel
        $prochainRappel = SanteRappel::where('animal_id', $animalId)
            ->where('statut', 'EN_ATTENTE')
            ->where('date_prevue', '>=', now())
            ->orderBy('date_prevue', 'asc')
            ->first();

        return [
            'animal_id' => $animal->id,
            'total_vaccinations' => $vaccinations,
            'total_traitements' => $traitements,
            'total_controles' => $controles,
            'rappels_en_attente' => $rappelsEnAttente,
            'rappels_en_retard' => $rappelsEnRetard,
            'rappels_realises' => $rappelsRealises,
            'dernier_evenement_sanitaire' => $dernierEvenement ? [
                'type' => $dernierEvenement->type->nom_type,
                'date' => $dernierEvenement->date_evenement,
                'description' => $dernierEvenement->description,
            ] : null,
            'prochain_rappel' => $prochainRappel ? [
                'type_rappel' => $prochainRappel->type_rappel,
                'date_prevue' => $prochainRappel->date_prevue,
                'jours_restants' => now()->diffInDays($prochainRappel->date_prevue, false),
            ] : null,
        ];
    }

    /**
     * Obtenir le résumé sanitaire pour une ferme.
     */
    public function resumeFerme(string $farmId): array
    {
        // Événements sanitaires du mois
        $evenementsMois = Evenement::where('farm_id', $farmId)
            ->sanitaires()
            ->whereMonth('date_evenement', now()->month)
            ->whereYear('date_evenement', now()->year)
            ->count();

        // Rappels à venir (7 jours)
        $rappelsAVenir = SanteRappel::where('farm_id', $farmId)
            ->where('statut', 'EN_ATTENTE')
            ->whereBetween('date_prevue', [now(), now()->addDays(7)])
            ->count();

        // Rappels en retard
        $rappelsEnRetard = SanteRappel::where('farm_id', $farmId)
            ->where('statut', 'EN_RETARD')
            ->count();

        // Animaux avec rappels en retard
        $animauxEnRetard = SanteRappel::where('farm_id', $farmId)
            ->where('statut', 'EN_RETARD')
            ->distinct('animal_id')
            ->count();

        return [
            'evenements_sanitaires_ce_mois' => $evenementsMois,
            'rappels_a_venir_7j' => $rappelsAVenir,
            'rappels_en_retard' => $rappelsEnRetard,
            'animaux_avec_rappels_en_retard' => $animauxEnRetard,
        ];
    }

    /**
     * Obtenir les alertes sanitaires pour une ferme.
     */
    public function alertesFerme(string $farmId): array
    {
        // Rappels en retard
        $rappelsEnRetard = SanteRappel::where('farm_id', $farmId)
            ->where('statut', 'EN_RETARD')
            ->with(['animal', 'animal.espece'])
            ->orderBy('date_prevue', 'asc')
            ->get()
            ->map(fn ($rappel) => [
                'id' => $rappel->id,
                'type_rappel' => $rappel->type_rappel,
                'date_prevue' => $rappel->date_prevue,
                'jours_retard' => now()->diffInDays($rappel->date_prevue, false),
                'animal' => [
                    'id' => $rappel->animal->id,
                    'nom' => $rappel->animal->nom,
                    'espece' => $rappel->animal->espece->nom ?? null,
                ],
            ]);

        // Rappels urgents (3 jours ou moins)
        $rappelsUrgents = SanteRappel::where('farm_id', $farmId)
            ->where('statut', 'EN_ATTENTE')
            ->whereBetween('date_prevue', [now(), now()->addDays(3)])
            ->with(['animal', 'animal.espece'])
            ->orderBy('date_prevue', 'asc')
            ->get()
            ->map(fn ($rappel) => [
                'id' => $rappel->id,
                'type_rappel' => $rappel->type_rappel,
                'date_prevue' => $rappel->date_prevue,
                'jours_restants' => now()->diffInDays($rappel->date_prevue, false),
                'animal' => [
                    'id' => $rappel->animal->id,
                    'nom' => $rappel->animal->nom,
                    'espece' => $rappel->animal->espece->nom ?? null,
                ],
            ]);

        return [
            'rappels_en_retard' => $rappelsEnRetard,
            'rappels_urgents' => $rappelsUrgents,
        ];
    }
}
