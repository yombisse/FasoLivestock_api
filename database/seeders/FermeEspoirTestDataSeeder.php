<?php

namespace Database\Seeders;

use App\Models\Animal;
use App\Models\Categorie;
use App\Models\Evenement;
use App\Models\Farm;
use App\Models\Lot;
use App\Models\Transaction;
use App\Models\TypeEvenement;
use App\Models\Espece;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FermeEspoirTestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Récupérer la ferme Espoir
        $fermeEspoir = Farm::where('name', 'Ferme Espoir')->first();
        
        if (!$fermeEspoir) {
            $this->command->error('Ferme Espoir non trouvée. Veuillez d\'abord créer cette ferme.');
            return;
        }

        $this->command->info('Création des données de test pour Ferme Espoir (ID: ' . $fermeEspoir->id . ')');

        // Supprimer les données existantes pour cette ferme (clean slate)
        $this->command->info('Nettoyage des données existantes...');
        
        // Supprimer dans l'ordre inverse des dépendances (avec soft deletes)
        DB::table('transactions')->where('farm_id', $fermeEspoir->id)->delete();
        DB::table('evenements')->where('farm_id', $fermeEspoir->id)->delete();
        DB::table('animals')->where('farm_id', $fermeEspoir->id)->delete();
        DB::table('lots')->where('farm_id', $fermeEspoir->id)->delete();

        // Récupérer les espèces
        $especeBovin = Espece::where('nom', 'Bovin')->first();
        $especeOvin = Espece::where('nom', 'Ovin')->first();

        // Récupérer les types d'événements
        $typeVaccination = TypeEvenement::where('nom_type', 'Vaccination')->first();
        $typeTraitement = TypeEvenement::where('nom_type', 'Traitement')->first();
        $typeSaillie = TypeEvenement::where('nom_type', 'Saillie')->first();
        $typeGestation = TypeEvenement::where('nom_type', 'Gestation confirmée')->first();
        $typeMiseBas = TypeEvenement::where('nom_type', 'Mise bas')->first();
        $typeAchat = TypeEvenement::where('nom_type', 'Achat')->first();
        $typeVente = TypeEvenement::where('nom_type', 'Vente')->first();

        // Récupérer les catégories de transactions
        $catVenteAnimaux = Categorie::where('nom_categorie', 'Vente d\'animaux')->first();
        $catAchatAnimaux = Categorie::where('nom_categorie', 'Achat d\'animaux')->first();
        $catSante = Categorie::where('nom_categorie', 'Santé')->first();

        $userId = $fermeEspoir->owner_id;

        // =========================================================
        // 1. CRÉATION DES LOTS
        // =========================================================
        $this->command->info('Création des lots...');
        
        $lotVaches = Lot::create([
            'id' => substr(Str::random(20), 0, 20),
            'farm_id' => $fermeEspoir->id,
            'nom_lot' => 'Lot Vaches Laitières',
            'nombre' => 0,
        ]);

        $lotTaureaux = Lot::create([
            'id' => substr(Str::random(20), 0, 20),
            'farm_id' => $fermeEspoir->id,
            'nom_lot' => 'Lot Taureaux',
            'nombre' => 0,
        ]);

        $lotVeaux = Lot::create([
            'id' => substr(Str::random(20), 0, 20),
            'farm_id' => $fermeEspoir->id,
            'nom_lot' => 'Lot Veaux',
            'nombre' => 0,
        ]);

        // =========================================================
        // 2. CRÉATION DES ANIMAUX
        // =========================================================
        $this->command->info('Création des animaux...');

        // Vaches femelles (pour reproduction)
        $vache1 = Animal::create([
            'id' => substr(Str::random(20), 0, 20),
            'farm_id' => $fermeEspoir->id,
            'lot_id' => $lotVaches->id,
            'espece_id' => $especeBovin->id,
            'nom' => 'Vache Bétel',
            'numero_identification' => 'BO-2024-001',
            'sexe' => 'femelle',
            'race' => 'Zébu',
            'date_naissance' => '2020-05-15',
            'poids' => 450.0,
            'statut' => 'SAIN',
            'last_modified_by' => $userId,
        ]);

        $vache2 = Animal::create([
            'id' => substr(Str::random(20), 0, 20),
            'farm_id' => $fermeEspoir->id,
            'lot_id' => $lotVaches->id,
            'espece_id' => $especeBovin->id,
            'nom' => 'Vache Jasmine',
            'numero_identification' => 'BO-2024-002',
            'sexe' => 'femelle',
            'race' => 'Zébu',
            'date_naissance' => '2019-08-20',
            'poids' => 480.0,
            'statut' => 'SAIN',
            'last_modified_by' => $userId,
        ]);

        $vache3 = Animal::create([
            'id' => substr(Str::random(20), 0, 20),
            'farm_id' => $fermeEspoir->id,
            'lot_id' => $lotVaches->id,
            'espece_id' => $especeBovin->id,
            'nom' => 'Vache Rose',
            'numero_identification' => 'BO-2024-003',
            'sexe' => 'femelle',
            'race' => 'Zébu',
            'date_naissance' => '2021-03-10',
            'poids' => 420.0,
            'statut' => 'SAIN',
            'last_modified_by' => $userId,
        ]);

        // Taureau mâle
        $taureau1 = Animal::create([
            'id' => substr(Str::random(20), 0, 20),
            'farm_id' => $fermeEspoir->id,
            'lot_id' => $lotTaureaux->id,
            'espece_id' => $especeBovin->id,
            'nom' => 'Taureau Hercule',
            'numero_identification' => 'BO-2024-004',
            'sexe' => 'male',
            'race' => 'Zébu',
            'date_naissance' => '2019-01-15',
            'poids' => 650.0,
            'statut' => 'SAIN',
            'last_modified_by' => $userId,
        ]);

        // Veaux
        $veau1 = Animal::create([
            'id' => substr(Str::random(20), 0, 20),
            'farm_id' => $fermeEspoir->id,
            'lot_id' => $lotVeaux->id,
            'espece_id' => $especeBovin->id,
            'mother_id' => $vache1->id,
            'nom' => 'Veau Petit',
            'numero_identification' => 'BO-2024-005',
            'sexe' => 'male',
            'race' => 'Zébu',
            'date_naissance' => '2024-06-01',
            'poids' => 80.0,
            'statut' => 'SAIN',
            'last_modified_by' => $userId,
        ]);

        // Mise à jour du nombre d'animaux dans les lots
        $lotVaches->update(['nombre' => 3]);
        $lotTaureaux->update(['nombre' => 1]);
        $lotVeaux->update(['nombre' => 1]);

        // =========================================================
        // 3. CRÉATION DES ÉVÉNEMENTS SANITAIRES
        // =========================================================
        $this->command->info('Création des événements sanitaires...');

        // Vaccination Vache Bétel
        Evenement::create([
            'id' => substr(Str::random(20), 0, 20),
            'farm_id' => $fermeEspoir->id,
            'animal_id' => $vache1->id,
            'type_evenement_id' => $typeVaccination->id,
            'date_evenement' => '2024-01-15',
            'description' => 'Vaccination contre la fièvre aphteuse',
            'cout' => 5000,
            'metadonnees' => json_encode([
                'nom_vaccin' => 'Vaccin Fièvre Aphteuse',
                'veterinaire' => 'Dr. Koné',
                'dosage' => '5ml',
                'lot_vaccin' => 'VA-2024-001'
            ]),
            'statut' => null,
            'last_modified_by' => $userId,
        ]);

        // Traitement Vache Jasmine
        Evenement::create([
            'id' => substr(Str::random(20), 0, 20),
            'farm_id' => $fermeEspoir->id,
            'animal_id' => $vache2->id,
            'type_evenement_id' => $typeTraitement->id,
            'date_evenement' => '2024-02-20',
            'description' => 'Traitement contre les parasites internes',
            'cout' => 7500,
            'metadonnees' => json_encode([
                'nom_medicament' => 'Ivermectine',
                'veterinaire' => 'Dr. Koné',
                'dosage' => '10ml',
                'duree' => '3 jours',
                'frequence' => '1 fois par jour'
            ]),
            'statut' => null,
            'last_modified_by' => $userId,
        ]);

        // =========================================================
        // 4. CRÉATION DES ÉVÉNEMENTS REPRODUCTIFS
        // =========================================================
        $this->command->info('Création des événements reproductifs...');

        // Saillie Vache Bétel
        $saillie1 = Evenement::create([
            'id' => substr(Str::random(20), 0, 20),
            'farm_id' => $fermeEspoir->id,
            'animal_id' => $vache1->id,
            'type_evenement_id' => $typeSaillie->id,
            'date_evenement' => '2024-03-01',
            'description' => 'Saillie naturelle avec Taureau Hercule',
            'metadonnees' => json_encode([
                'male_id' => $taureau1->id,
                'type_saillie' => 'naturelle'
            ]),
            'statut' => 'TERMINE',
            'last_modified_by' => $userId,
        ]);

        // Gestation confirmée Vache Bétel
        $gestation1 = Evenement::create([
            'id' => substr(Str::random(20), 0, 20),
            'farm_id' => $fermeEspoir->id,
            'animal_id' => $vache1->id,
            'type_evenement_id' => $typeGestation->id,
            'date_evenement' => '2024-04-15',
            'description' => 'Gestation confirmée par échographie',
            'metadonnees' => json_encode([
                'saillie_id' => $saillie1->id,
                'methode_confirmation' => 'echographie',
                'duree_gestation_estimee' => 285
            ]),
            'statut' => 'EN_COURS',
            'last_modified_by' => $userId,
        ]);

        // Saillie Vache Rose
        $saillie2 = Evenement::create([
            'id' => substr(Str::random(20), 0, 20),
            'farm_id' => $fermeEspoir->id,
            'animal_id' => $vache3->id,
            'type_evenement_id' => $typeSaillie->id,
            'date_evenement' => '2024-05-10',
            'description' => 'Saillie naturelle avec Taureau Hercule',
            'metadonnees' => json_encode([
                'male_id' => $taureau1->id,
                'type_saillie' => 'naturelle'
            ]),
            'statut' => 'TERMINE',
            'last_modified_by' => $userId,
        ]);

        // =========================================================
        // 5. CRÉATION DES TRANSACTIONS FINANCIÈRES
        // =========================================================
        $this->command->info('Création des transactions financières...');

        // Achat d'alimentation
        Transaction::create([
            'id' => substr(Str::random(20), 0, 20),
            'farm_id' => $fermeEspoir->id,
            'categorie_id' => $catAchatAnimaux->id,
            'type_transaction' => 'SORTIE',
            'montant' => 150000,
            'date_transaction' => '2024-01-10',
            'description' => 'Achat de concentré pour bétail',
            'user_id' => $userId,
        ]);

        // Vente de lait
        Transaction::create([
            'id' => substr(Str::random(20), 0, 20),
            'farm_id' => $fermeEspoir->id,
            'categorie_id' => $catVenteAnimaux->id,
            'type_transaction' => 'ENTREE',
            'montant' => 75000,
            'date_transaction' => '2024-06-15',
            'description' => 'Vente de lait (500 litres)',
            'user_id' => $userId,
        ]);

        // Dépense santé (vaccination)
        Transaction::create([
            'id' => substr(Str::random(20), 0, 20),
            'farm_id' => $fermeEspoir->id,
            'categorie_id' => $catSante->id,
            'type_transaction' => 'SORTIE',
            'montant' => 5000,
            'date_transaction' => '2024-01-15',
            'description' => 'Vaccination Vache Bétel',
            'evenement_id' => Evenement::where('animal_id', $vache1->id)
                ->where('type_evenement_id', $typeVaccination->id)
                ->first()->id,
            'user_id' => $userId,
        ]);

        // =========================================================
        // 6. CRÉATION DES ÉVÉNEMENTS DE MOUVEMENT (ACHAT/VENTE)
        // =========================================================
        $this->command->info('Création des événements de mouvement...');

        // Achat d'un animal (simulé avec un animal créé)
        $animalAchete = Animal::create([
            'id' => substr(Str::random(20), 0, 20),
            'farm_id' => $fermeEspoir->id,
            'lot_id' => $lotVaches->id,
            'espece_id' => $especeBovin->id,
            'nom' => 'Vache Achetée',
            'numero_identification' => 'BO-2024-006',
            'sexe' => 'femelle',
            'race' => 'Zébu',
            'date_naissance' => '2022-04-01',
            'poids' => 400.0,
            'statut' => 'SAIN',
            'last_modified_by' => $userId,
        ]);

        Evenement::create([
            'id' => substr(Str::random(20), 0, 20),
            'farm_id' => $fermeEspoir->id,
            'animal_id' => $animalAchete->id,
            'type_evenement_id' => $typeAchat->id,
            'date_evenement' => '2024-03-15',
            'description' => 'Achat de vache au marché de Bobo',
            'cout' => 250000,
            'metadonnees' => json_encode([
                'prix_achat' => 250000,
                'provenance' => 'Marché de Bobo',
                'vendeur' => 'Éleveur Sanou'
            ]),
            'statut' => null,
            'last_modified_by' => $userId,
        ]);

        // Transaction associée à l'achat
        Transaction::create([
            'id' => substr(Str::random(20), 0, 20),
            'farm_id' => $fermeEspoir->id,
            'categorie_id' => $catAchatAnimaux->id,
            'type_transaction' => 'SORTIE',
            'montant' => 250000,
            'date_transaction' => '2024-03-15',
            'description' => 'Achat Vache Achetée',
            'evenement_id' => Evenement::where('animal_id', $animalAchete->id)
                ->where('type_evenement_id', $typeAchat->id)
                ->first()->id,
            'user_id' => $userId,
        ]);

        // Mise à jour du nombre d'animaux dans le lot
        $lotVaches->update(['nombre' => 4]);

        $this->command->info('Données de test créées avec succès pour Ferme Espoir !');
        $this->command->info('Résumé:');
        $this->command->info('- 3 lots créés');
        $this->command->info('- 6 animaux créés (3 vaches, 1 taureau, 1 veau, 1 vache achetée)');
        $this->command->info('- 2 événements sanitaires créés');
        $this->command->info('- 4 événements reproductifs créés');
        $this->command->info('- 3 transactions financières créées');
        $this->command->info('- 1 événement d\'achat créé');
    }
}
