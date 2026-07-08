<?php

namespace App\Observers;

use App\Models\Evenement;
use App\Models\TypeEvenement;

class EvenementObserver
{
    /**
     * Handle the Evenement "created" event.
     */
    public function created(Evenement $evenement): void
    {
        $typeNom = $evenement->type?->nom_type;

        // 1. Si type = SAILLIE : ne rien faire automatiquement sur le statut animal
        if ($typeNom === 'Saillie') {
            // La saillie ne confirme pas la gestation
            return;
        }

        // 2. Si type = GESTATION_CONFIRMEE : mettre statut = EN_COURS sur cet événement
        if ($typeNom === 'Gestation confirmée') {
            $evenement->update(['statut' => Evenement::STATUT_EN_COURS]);
            
            // Optionnel : clore les événements SAILLIE EN_COURS de cet animal
            Evenement::where('animal_id', $evenement->animal_id)
                ->whereHas('type', fn($q) => $q->where('nom_type', 'Saillie'))
                ->where('statut', Evenement::STATUT_EN_COURS)
                ->update(['statut' => Evenement::STATUT_TERMINE, 'date_fin' => now()]);
            
            return;
        }

        // 3. Si type = MISE_BAS : trouver le dernier événement GESTATION_CONFIRMEE EN_COURS
        if ($typeNom === 'Mise bas') {
            $gestationEnCours = Evenement::where('animal_id', $evenement->animal_id)
                ->whereHas('type', fn($q) => $q->where('nom_type', 'Gestation confirmée'))
                ->where('statut', Evenement::STATUT_EN_COURS)
                ->latest('date_evenement')
                ->first();

            if ($gestationEnCours) {
                $gestationEnCours->update([
                    'statut' => Evenement::STATUT_TERMINE,
                    'date_fin' => now()
                ]);
            }
            
            return;
        }

        // 4. Si type = mouvement (VENTE, DECES, etc.) : clore tous les événements reproductifs EN_COURS
        if (in_array(strtoupper($typeNom ?? ''), Evenement::TYPES_MOUVEMENT)) {
            Evenement::where('animal_id', $evenement->animal_id)
                ->where('statut', Evenement::STATUT_EN_COURS)
                ->update(['statut' => Evenement::STATUT_TERMINE, 'date_fin' => now()]);
        }
    }

    /**
     * Handle the Evenement "updated" event.
     */
    public function updated(Evenement $evenement): void
    {
        // Si statut passe à TERMINE et type = GESTATION_CONFIRMEE : mettre date_fin = aujourd'hui
        if ($evenement->isDirty('statut') 
            && $evenement->statut === Evenement::STATUT_TERMINE
            && $evenement->type?->nom_type === 'Gestation confirmée'
            && !$evenement->date_fin) {
            $evenement->update(['date_fin' => now()]);
        }
    }
}
