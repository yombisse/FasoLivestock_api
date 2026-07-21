<?php

namespace Database\Seeders;

use App\Models\Espece;
use App\Models\EspeceParametre;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeder des paramètres zootechniques par espèce.
 *
 * Ces valeurs sont quasi immuables (biologie de l'espèce) et servent de base
 * au système de rappels (mise bas prévue, prochain vaccin, âge de reproduction...).
 * Elles restent modifiables via l'interface admin si un éleveur/vétérinaire
 * souhaite les ajuster à sa race ou à son contexte local, mais les valeurs
 * par défaut ci-dessous reposent sur des données zootechniques généralement
 * admises (moyennes de plusieurs sources vétérinaires et de vulgarisation
 * agricole : Merck Veterinary Manual, universités d'extension agricole
 * américaines - Missouri/Alabama -, littérature zootechnique sur le zébu
 * et les races sahéliennes). Pour les races locales sahéliennes
 * (zébu, mouton et chèvre Sahel, âne, dromadaire), les fourchettes hautes/
 * basses ont été resserrées vers les valeurs les plus fréquemment observées
 * en élevage traditionnel/semi-intensif ouest-africain plutôt que vers les
 * standards de races européennes à haute performance.
 *
 * Sources générales consultées :
 * - Merck Veterinary Manual, table "Approximate Gestation Periods"
 * - Missouri University Extension (G2611 - Breeding Season Sheep & Goats ;
 *   G2500 - Care of Mots from Farrowing to Weaning)
 * - Alabama Cooperative Extension System - Reproductive Management Dairy
 *   Goats and Sheep
 * - Littérature zootechnique sur le zébu (gestation 285-295 j, sevrage 6-9
 *   mois, poids naissance 20-35 kg)
 * - National Veterinary Institute - programme de vaccination Newcastle
 *   (rappels tous les ~8-12 semaines en période de ponte)
 * - Données comparatives de gestation (cheval, âne, dromadaire, lapin)
 */
class EspeceParametreSeeder extends Seeder
{
    public function run(): void
    {
        $parametres = [
            // -----------------------------------------------------------
            // BOVIN (zébu et bovins domestiques)
            // Gestation ~285j (zébu: 285-295j) ; puberté 18-30 mois,
            // mise à la reproduction généralement retenue à 24 mois ;
            // naissance simple (jumeaux <1%) ; sevrage 6-9 mois ;
            // poids naissance 20-35 kg ; poids adulte zébu Sahel ~250-350 kg
            // -----------------------------------------------------------
            'Bovin' => [
                'duree_gestation_jours'    => 285,
                'age_reproduction_mois'    => 24,
                'nombre_petits_typique'    => 1,
                'intervalle_vaccin_jours'  => 365,
                'age_sevrage_jours'        => 210,
                'poids_naissance_moyen_kg' => 25.00,
                'poids_adulte_moyen_kg'    => 300.00,
            ],

            // -----------------------------------------------------------
            // OVIN (moutons et brebis)
            // Gestation 144-152j (moy. 147j) ; puberté 7-10 mois ;
            // sevrage naturel 6-8 semaines, souvent prolongé à ~3 mois
            // en élevage traditionnel ; poids naissance ~3-4 kg ;
            // poids adulte mouton Sahel ~35-45 kg
            // -----------------------------------------------------------
            'Ovin' => [
                'duree_gestation_jours'    => 147,
                'age_reproduction_mois'    => 8,
                'nombre_petits_typique'    => 1,
                'intervalle_vaccin_jours'  => 365,
                'age_sevrage_jours'        => 90,
                'poids_naissance_moyen_kg' => 3.00,
                'poids_adulte_moyen_kg'    => 40.00,
            ],

            // -----------------------------------------------------------
            // CAPRIN (chèvres et boucs)
            // Gestation 145-155j (moy. 150j) ; puberté similaire aux ovins ;
            // sevrage 6-8 semaines (souvent étendu à ~3 mois en élevage
            // traditionnel) ; poids naissance 2-3 kg ; poids adulte
            // chèvre Sahel ~30-40 kg
            // -----------------------------------------------------------
            'Caprin' => [
                'duree_gestation_jours'    => 150,
                'age_reproduction_mois'    => 8,
                'nombre_petits_typique'    => 1,
                'intervalle_vaccin_jours'  => 365,
                'age_sevrage_jours'        => 90,
                'poids_naissance_moyen_kg' => 2.50,
                'poids_adulte_moyen_kg'    => 35.00,
            ],

            // -----------------------------------------------------------
            // PORCIN (porcs et truies)
            // Gestation ~114j ("3 mois, 3 semaines, 3 jours") ; puberté
            // 6-8 mois ; portée typique 8-14 porcelets ; sevrage
            // industriel 21-28j, souvent plus tardif (4-6 semaines) en
            // petit élevage ; poids naissance ~1.2-1.5 kg
            // -----------------------------------------------------------
            'Porcin' => [
                'duree_gestation_jours'    => 114,
                'age_reproduction_mois'    => 8,
                'nombre_petits_typique'    => 10,
                'intervalle_vaccin_jours'  => 180,
                'age_sevrage_jours'        => 42,
                'poids_naissance_moyen_kg' => 1.30,
                'poids_adulte_moyen_kg'    => 120.00,
            ],

            // -----------------------------------------------------------
            // VOLAILLE (poulets, pintades, dindes, canards)
            // Incubation 21j (référence poule) ; entrée en ponte
            // ~20-24 semaines (~5 mois) ; ponte/couvée typique 10-15
            // œufs ; rappels vaccinaux Newcastle tous les ~8-12
            // semaines en période de ponte ; sevrage/autonomie du
            // poussin ~6 semaines ; poids poussin ~40g, poids adulte
            // variable selon l'espèce de volaille (moyenne poulet ~2 kg)
            // -----------------------------------------------------------
            'Volaille' => [
                'duree_gestation_jours'    => 21,
                'age_reproduction_mois'    => 5,
                'nombre_petits_typique'    => 12,
                'intervalle_vaccin_jours'  => 90,
                'age_sevrage_jours'        => 42,
                'poids_naissance_moyen_kg' => 0.04,
                'poids_adulte_moyen_kg'    => 2.00,
            ],

            // -----------------------------------------------------------
            // ÉQUIN (chevaux et poneys)
            // Gestation ~340j (11 mois, fourchette 320-365j) ; mise à la
            // reproduction recommandée à partir de 3 ans (36 mois) ;
            // naissance simple ; sevrage du poulain ~6 mois ; poids
            // naissance ~40-50 kg
            // -----------------------------------------------------------
            'Équin' => [
                'duree_gestation_jours'    => 340,
                'age_reproduction_mois'    => 36,
                'nombre_petits_typique'    => 1,
                'intervalle_vaccin_jours'  => 365,
                'age_sevrage_jours'        => 180,
                'poids_naissance_moyen_kg' => 45.00,
                'poids_adulte_moyen_kg'    => 350.00,
            ],

            // -----------------------------------------------------------
            // ASIN (ânes et baudets)
            // Gestation ~365j (12 mois, fourchette 348-395j), la plus
            // longue des équidés domestiques ; puberté/mise à la
            // reproduction ~2 ans ; naissance simple ; sevrage ~6 mois
            // -----------------------------------------------------------
            'Asin' => [
                'duree_gestation_jours'    => 365,
                'age_reproduction_mois'    => 24,
                'nombre_petits_typique'    => 1,
                'intervalle_vaccin_jours'  => 365,
                'age_sevrage_jours'        => 180,
                'poids_naissance_moyen_kg' => 25.00,
                'poids_adulte_moyen_kg'    => 150.00,
            ],

            // -----------------------------------------------------------
            // CAMELIN (dromadaires)
            // Gestation la plus longue de l'élevage courant : ~390j
            // (13 mois, fourchette 370-410j) ; maturité sexuelle tardive,
            // mise à la reproduction ~4 ans (48 mois) ; naissance simple
            // (jumeaux exceptionnels) ; sevrage tardif, souvent ~12 mois
            // -----------------------------------------------------------
            'Camelin' => [
                'duree_gestation_jours'    => 390,
                'age_reproduction_mois'    => 48,
                'nombre_petits_typique'    => 1,
                'intervalle_vaccin_jours'  => 365,
                'age_sevrage_jours'        => 365,
                'poids_naissance_moyen_kg' => 35.00,
                'poids_adulte_moyen_kg'    => 450.00,
            ],

            // -----------------------------------------------------------
            // LAPIN (lapins d'élevage)
            // Gestation très courte ~31j (28-34j) ; maturité sexuelle
            // ~5 mois pour une mise à la reproduction raisonnable ;
            // portée typique 4-8 lapereaux ; sevrage 4-6 semaines ;
            // poids naissance ~50-70g
            // -----------------------------------------------------------
            'Lapin' => [
                'duree_gestation_jours'    => 31,
                'age_reproduction_mois'    => 5,
                'nombre_petits_typique'    => 6,
                'intervalle_vaccin_jours'  => 180,
                'age_sevrage_jours'        => 35,
                'poids_naissance_moyen_kg' => 0.06,
                'poids_adulte_moyen_kg'    => 4.00,
            ],

            // -----------------------------------------------------------
            // CANIDÉ (chiens et autres canidés d'élevage/garde)
            // Gestation ~63j (constante bien établie, peu de variation
            // inter-races) ; puberté 6-12 mois, mise à la reproduction
            // raisonnable à partir de 12 mois ; portée moyenne ~6 chiots
            // (grande variabilité selon le gabarit) ; sevrage ~8 semaines ;
            // rappel vaccinal annuel (rage/CHPPi)
            // -----------------------------------------------------------
            'Canidé' => [
                'duree_gestation_jours'    => 63,
                'age_reproduction_mois'    => 12,
                'nombre_petits_typique'    => 6,
                'intervalle_vaccin_jours'  => 365,
                'age_sevrage_jours'        => 56,
                'poids_naissance_moyen_kg' => 0.40,
                'poids_adulte_moyen_kg'    => 20.00,
            ],
        ];

        foreach ($parametres as $nomEspece => $donnees) {
            $espece = Espece::where('nom', $nomEspece)->first();

            if (!$espece) {
                $this->command->warn("⚠️  Espèce '{$nomEspece}' introuvable, paramètres ignorés. Avez-vous exécuté EspeceSeeder ?");
                continue;
            }

            EspeceParametre::updateOrCreate(
                ['espece_id' => $espece->id],
                array_merge($donnees, [
                    'id' => substr(Str::random(20), 0, 20),
                ])
            );
        }

        $this->command->info('✅ Paramètres zootechniques des espèces préremplis avec succès.');
    }
}
