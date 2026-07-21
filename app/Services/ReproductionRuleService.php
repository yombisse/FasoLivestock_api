<?php

namespace App\Services;

use App\Models\Animal;
use App\Models\Evenement;
use App\Models\TypeEvenement;
use Illuminate\Validation\ValidationException;

class ReproductionRuleService
{
    /**
     * Valider qu'une saillie est possible pour cet animal
     * 
     * @throws ValidationException
     */
    public function validerSaillie(Animal $animal, string $dateEvenement, ?string $maleId = null): void
    {
        // Rejeter si l'animal n'est pas une femelle
        if ($animal->sexe !== 'femelle') {
            throw ValidationException::withMessages([
                'animal_id' => 'Une saillie ne peut être enregistrée que sur un animal femelle.'
            ]);
        }

        // Rejeter si l'animal n'est pas en âge de reproduire (uniquement si date_naissance connue)
        if ($animal->date_naissance && $animal->espece && $animal->espece->parametre) {
            if (!$animal->espece->parametre->estEnAgeDeReproduire($animal->date_naissance)) {
                $ageRequis = $animal->espece->parametre->age_reproduction_mois;
                $especeNom = $animal->espece->nom;
                $ageActuel = now()->diffInMonths($animal->date_naissance);
                
                throw ValidationException::withMessages([
                    'animal_id' => "Âge de reproduction non atteint ({$ageRequis} mois requis pour l'espèce {$especeNom}, animal actuel : {$ageActuel} mois)."
                ]);
            }
        }

        // Si un mâle est spécifié (saillie naturelle), vérifier que c'est bien un mâle
        if ($maleId) {
            $male = Animal::find($maleId);
            if (!$male) {
                throw ValidationException::withMessages([
                    'male_id' => 'Le mâle spécifié n\'existe pas.'
                ]);
            }
            if ($male->sexe !== 'male') {
                throw ValidationException::withMessages([
                    'male_id' => 'L\'animal spécifié comme mâle doit être de sexe mâle.'
                ]);
            }
        }

        // Rejeter si une gestation EN_COURS existe déjà
        $gestationEnCours = Evenement::where('animal_id', $animal->id)
            ->whereHas('type', fn($q) => $q->where('nom_type', 'Gestation'))
            ->where('statut', Evenement::STATUT_EN_COURS)
            ->exists();

        if ($gestationEnCours) {
            throw ValidationException::withMessages([
                'animal_id' => 'Impossible de créer une saillie : cet animal a déjà une gestation en cours.'
            ]);
        }
    }

    /**
     * Valider qu'une confirmation de gestation est possible
     *
     * @throws ValidationException
     */
    public function validerGestationConfirmee(Animal $animal, string $dateEvenement): void
    {
        // Rejeter si l'animal n'est pas une femelle
        if ($animal->sexe !== 'femelle') {
            throw ValidationException::withMessages([
                'animal_id' => 'Une confirmation de gestation ne peut être enregistrée que sur un animal femelle.'
            ]);
        }

        // Rejeter si aucune saillie n'existe (sans statut ou EN_COURS)
        $saillieEnCours = Evenement::where('animal_id', $animal->id)
            ->whereHas('type', fn($q) => $q->where('nom_type', 'Saillie'))
            ->where(function($q) {
                $q->where('statut', Evenement::STATUT_EN_COURS)
                  ->orWhereNull('statut');
            })
            ->latest('date_evenement')
            ->first();

        if (!$saillieEnCours) {
            throw ValidationException::withMessages([
                'animal_id' => 'Impossible de confirmer une gestation : aucune saillie en cours trouvée pour cet animal.'
            ]);
        }

        // Rejeter si une gestation EN_COURS existe déjà
        $gestationEnCours = Evenement::where('animal_id', $animal->id)
            ->whereHas('type', fn($q) => $q->where('nom_type', 'Gestation'))
            ->where('statut', Evenement::STATUT_EN_COURS)
            ->exists();

        if ($gestationEnCours) {
            throw ValidationException::withMessages([
                'animal_id' => 'Impossible de confirmer une gestation : une gestation est déjà en cours pour cet animal.'
            ]);
        }

        // Optionnel : rejeter si incohérence temporelle
        if ($saillieEnCours->date_evenement > $dateEvenement) {
            throw ValidationException::withMessages([
                'date_evenement' => 'Incohérence temporelle : la date de confirmation de gestation ne peut pas être antérieure à la date de saillie.'
            ]);
        }
    }

    /**
     * Valider qu'une mise bas est possible
     *
     * @throws ValidationException
     */
    public function validerMiseBas(Animal $animal, string $dateEvenement): void
    {
        // Rejeter si aucune gestation EN_COURS n'existe
        $gestationEnCours = Evenement::where('animal_id', $animal->id)
            ->whereHas('type', fn($q) => $q->where('nom_type', 'Gestation'))
            ->where('statut', Evenement::STATUT_EN_COURS)
            ->latest('date_evenement')
            ->first();

        if (!$gestationEnCours) {
            throw ValidationException::withMessages([
                'animal_id' => 'Impossible de créer un événement MISE_BAS : aucune gestation en cours pour cet animal.'
            ]);
        }

        // Optionnel : rejeter si incohérence temporelle
        if ($gestationEnCours->date_evenement > $dateEvenement) {
            throw ValidationException::withMessages([
                'date_evenement' => 'Incohérence temporelle : la date de mise bas ne peut pas être antérieure à la date de confirmation de gestation.'
            ]);
        }
    }
}
