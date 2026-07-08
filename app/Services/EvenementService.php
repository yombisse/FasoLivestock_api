<?php

namespace App\Services;

use App\Models\Evenement;
use App\Models\Animal;
use App\Models\TypeEvenement;
use Illuminate\Support\Facades\DB;

class EvenementService
{
    private ActivityLogService $activityLog;

    public function __construct(ActivityLogService $activityLog)
    {
        $this->activityLog = $activityLog;
    }

    /**
     * Créer un mouvement.
     * Remplace l'observer booted() de Evenement.
     * NOTE: La transaction doit être gérée par l'appelant (EvenementMouvementService).
     * NOTE: La mise à jour du statut de l'animal est gérée par l'appelant.
     *
     * @param array $data Données de l'événement
     * @param Animal $animal Animal concerné
     * @return Evenement
     */
    public function creerMouvement(array $data, Animal $animal): Evenement
    {
        // Dériver la categorie du TypeEvenement
        if (!isset($data['categorie']) && isset($data['type_evenement_id'])) {
            $typeEvenement = TypeEvenement::find($data['type_evenement_id']);
            $data['categorie'] = $typeEvenement ? $typeEvenement->categorie : 'MOUVEMENT';
        }

        $evenement = Evenement::create($data);

        // Changer la ferme de l'animal si c'est un transfert avec destination
        if (isset($data['farm_destination_id']) && $data['farm_destination_id']) {
            $typeEvenement = TypeEvenement::find($data['type_evenement_id']);
            $nomType = $typeEvenement ? strtoupper($typeEvenement->nom_type) : '';

            if ($nomType === 'TRANSFERT') {
                $animal->update([
                    'farm_id' => $data['farm_destination_id']
                ]);
            }
        }

        $this->activityLog->log('created', $evenement, null, $data);

        return $evenement->fresh();
    }

    /**
     * Créer un événement sanitaire (sans modification de l'animal).
     *
     * @param array $data Données de l'événement
     * @return Evenement
     */
    public function creerEvenementSanitaire(array $data): Evenement
    {
        // Dériver la categorie du TypeEvenement
        if (!isset($data['categorie']) && isset($data['type_evenement_id'])) {
            $typeEvenement = TypeEvenement::find($data['type_evenement_id']);
            $data['categorie'] = $typeEvenement ? $typeEvenement->categorie : 'SANITAIRE';
        }

        $data['sync_status'] = 'synced';
        $data['version'] = 1;

        $evenement = Evenement::create($data);

        $this->activityLog->log('created', $evenement, null, $data);

        return $evenement->fresh();
    }
}
