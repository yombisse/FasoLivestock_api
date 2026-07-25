<?php

namespace App\Observers;

use App\Models\Evenement;
use App\Models\TypeEvenement;
use App\Models\SanteRappel;
use App\Models\EspeceParametre;
use App\Services\EvenementTransactionService;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;

class EvenementObserver
{
    /**
     * Handle the Evenement "created" event.
     */
    public function created(Evenement $evenement): void
    {
        $typeNom = $evenement->type?->nom_type;

        // 1. Créer transaction automatique pour événements reproductifs/sanitaires avec coût
        // NOTE: Désactivé ici car géré par EvenementReproductionService::store()
        // pour éviter la duplication et permettre de passer le userId explicitement
        /*
        if (isset($evenement->cout) && $evenement->cout >= 0) {
            $libelleCategorie = $this->getCategorieLibelle($typeNom);
            if ($libelleCategorie) {
                $evenementTransactionService = App::make(EvenementTransactionService::class);
                $evenementTransactionService->creerTransactionDepuisEvenement(
                    $evenement,
                    $evenement->cout,
                    $libelleCategorie
                );
            }
        }
        */

        // 2. Créer rappels automatiques pour événements sanitaires
        $this->creerRappelSanitaire($evenement, $typeNom);

        // 3. Créer rappels automatiques pour événements reproductifs
        $this->creerRappelReproductif($evenement, $typeNom);

        // 4. Si type = SAILLIE : ne rien faire automatiquement sur le statut animal
        if ($typeNom === 'Saillie') {
            // La saillie ne confirme pas la gestation
            return;
        }

        // 5. Si type = GESTATION_CONFIRMEE : mettre statut = EN_COURS sur cet événement
        if ($typeNom === 'Gestation confirmée') {
            $evenement->update(['statut' => Evenement::STATUT_EN_COURS]);

            // Optionnel : clore les événements SAILLIE EN_COURS de cet animal
            Evenement::where('animal_id', $evenement->animal_id)
                ->whereHas('type', fn($q) => $q->where('nom_type', 'Saillie'))
                ->where('statut', Evenement::STATUT_EN_COURS)
                ->update(['statut' => Evenement::STATUT_TERMINE, 'date_fin' => now()]);

            return;
        }

        // 6. Si type = MISE_BAS : trouver le dernier événement GESTATION_CONFIRMEE EN_COURS
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

        // 7. Si type = mouvement (VENTE, DECES, etc.) : clore tous les événements reproductifs EN_COURS
        if (in_array(strtoupper($typeNom ?? ''), Evenement::TYPES_MOUVEMENT)) {
            Evenement::where('animal_id', $evenement->animal_id)
                ->where('statut', Evenement::STATUT_EN_COURS)
                ->update(['statut' => Evenement::STATUT_TERMINE, 'date_fin' => now()]);
        }
    }

    /**
     * Créer un rappel sanitaire automatique basé sur les paramètres de l'espèce
     */
    private function creerRappelSanitaire(Evenement $evenement, ?string $typeNom): void
    {
        if (!$evenement->animal || !$typeNom) {
            return;
        }

        $typeNom = strtoupper($typeNom);

        // Mapper les types d'événements vers les types de rappels
        $typeRappelMap = [
            'VACCINATION' => SanteRappel::TYPE_VACCINATION,
            'TRAITEMENT' => SanteRappel::TYPE_TRAITEMENT,
            'CONTRÔLE' => SanteRappel::TYPE_CONTROLE,
        ];

        $typeRappel = $typeRappelMap[$typeNom] ?? null;
        if (!$typeRappel) {
            return;
        }

        // Récupérer les paramètres de l'espèce
        $especeParametre = EspeceParametre::where('espece_id', $evenement->animal->espece_id)->first();
        if (!$especeParametre) {
            return;
        }

        // Calculer la date du prochain rappel selon le type
        $datePrev = null;
        switch ($typeRappel) {
            case SanteRappel::TYPE_VACCINATION:
                // Vaccination : utilise la configuration de l'espèce
                $joursIntervalle = $especeParametre->intervalle_vaccin_jours;
                if ($joursIntervalle && $joursIntervalle > 0) {
                    $datePrev = $evenement->date_evenement->addDays($joursIntervalle);
                }
                break;
            case SanteRappel::TYPE_TRAITEMENT:
                // Traitement : utilise la date suggérée dans les métadonnées si fournie
                if (isset($evenement->metadonnees['date_rappel_suggeree'])) {
                    $datePrev = \Carbon\Carbon::parse($evenement->metadonnees['date_rappel_suggeree']);
                }
                break;
            case SanteRappel::TYPE_CONTROLE:
                // Contrôle : utilise la date suggérée dans les métadonnées si fournie
                if (isset($evenement->metadonnees['date_prochain_controle'])) {
                    $datePrev = \Carbon\Carbon::parse($evenement->metadonnees['date_prochain_controle']);
                }
                break;
        }

        if (!$datePrev) {
            return;
        }

        SanteRappel::create([
            'id' => substr(Str::random(20), 0, 20),
            'farm_id' => $evenement->farm_id,
            'animal_id' => $evenement->animal_id,
            'type_rappel' => $typeRappel,
            'date_prevue' => $datePrev,
            'statut' => SanteRappel::STATUT_EN_ATTENTE,
            'evenement_id' => $evenement->id,
            'sync_status' => 'synced',
            'last_modified_by' => auth()->id(),
            'version' => 1,
        ]);
    }

    /**
     * Créer un rappel reproductif automatique basé sur les paramètres de l'espèce
     */
    private function creerRappelReproductif(Evenement $evenement, ?string $typeNom): void
    {
        if (!$evenement->animal || !$typeNom) {
            return;
        }

        $typeNom = strtoupper($typeNom);

        // Créer un rappel de mise bas prévue lors de la confirmation de gestation
        if ($typeNom === 'GESTATION CONFIRMÉE') {
            $especeParametre = EspeceParametre::where('espece_id', $evenement->animal->espece_id)->first();
            if (!$especeParametre || !$especeParametre->duree_gestation_jours) {
                return;
            }

            $dateMiseBasPrev = $evenement->date_evenement->addDays($especeParametre->duree_gestation_jours);

            SanteRappel::create([
                'id' => substr(Str::random(20), 0, 20),
                'farm_id' => $evenement->farm_id,
                'animal_id' => $evenement->animal_id,
                'type_rappel' => 'MISE_BAS',
                'date_prevue' => $dateMiseBasPrev,
                'statut' => SanteRappel::STATUT_EN_ATTENTE,
                'evenement_id' => $evenement->id,
                'note' => 'Mise bas prévue selon durée de gestation de l\'espèce',
                'sync_status' => 'synced',
                'last_modified_by' => auth()->id(),
                'version' => 1,
            ]);
        }

        // Créer un rappel de chaleur suivant lors d'une chaleur
        if ($typeNom === 'CHALEUR' && $evenement->animal->sexe === 'femelle') {
            // Intervalle moyen entre chaleurs : 21 jours pour la plupart des espèces
            $dateChaleurSuiv = $evenement->date_evenement->addDays(21);

            SanteRappel::create([
                'id' => substr(Str::random(20), 0, 20),
                'farm_id' => $evenement->farm_id,
                'animal_id' => $evenement->animal_id,
                'type_rappel' => 'CHALEUR',
                'date_prevue' => $dateChaleurSuiv,
                'statut' => SanteRappel::STATUT_EN_ATTENTE,
                'evenement_id' => $evenement->id,
                'note' => 'Chaleur suivante estimée (intervalle moyen 21 jours)',
                'sync_status' => 'synced',
                'last_modified_by' => auth()->id(),
                'version' => 1,
            ]);
        }
    }

    /**
     * Get category label based on event type
     */
    private function getCategorieLibelle(?string $typeNom): ?string
    {
        if (!$typeNom) {
            return null;
        }

        $typeNom = strtoupper($typeNom);

        // Événements reproductifs
        $reproductionMap = [
            'SAILLIE' => 'Saillie',
            'GESTATION CONFIRMÉE' => 'Gestation confirmée',
            'MISE BAS' => 'Mise bas',
            'CHALEUR' => 'Chaleur',
            'NAISSANCE' => 'Naissance',
        ];

        // Événements sanitaires (selon TypeEvenementSeeder)
        $santeMap = [
            'VACCINATION' => 'Vaccination',
            'TRAITEMENT' => 'Traitement',
            'CONTRÔLE' => 'Contrôle',
            'PESÉE' => 'Pesée',
            'AUTRE' => 'Autre',
        ];

        return $reproductionMap[$typeNom] ?? $santeMap[$typeNom] ?? null;
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
