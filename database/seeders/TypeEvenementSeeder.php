<?php

namespace Database\Seeders;

use App\Models\TypeEvenement;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TypeEvenementSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            [
                'nom_type' => 'Chaleur',
                'description' => 'Détection de chaleur chez l\'animal femelle',
                'categorie' => 'REPRODUCTION',
                'is_system' => true,
            ],
            [
                'nom_type' => 'Saillie',
                'description' => 'Accouplement ou insémination de l\'animal',
                'categorie' => 'REPRODUCTION',
                'is_system' => true,
            ],
            [
                'nom_type' => 'Gestation confirmée',
                'description' => 'Confirmation de la gestation par examen vétérinaire',
                'categorie' => 'REPRODUCTION',
                'is_system' => true,
            ],
            [
                'nom_type' => 'Mise bas',
                'description' => 'Naissance des petits',
                'categorie' => 'REPRODUCTION',
                'is_system' => true,
            ],
            [
                'nom_type' => 'NAISSANCE',
                'description' => 'Naissance d\'un animal',
                'categorie' => 'REPRODUCTION',
                'is_system' => true,
            ],
            [
                'nom_type' => 'Vaccination',
                'description' => 'Vaccination préventive ou curative',
                'categorie' => 'SANITAIRE',
                'is_system' => true,
            ],
            [
                'nom_type' => 'Traitement',
                'description' => 'Traitement médical ou vétérinaire',
                'categorie' => 'SANITAIRE',
                'is_system' => true,
            ],
            [
                'nom_type' => 'Vente',
                'description' => 'Vente d\'un animal',
                'categorie' => 'MOUVEMENT',
                'is_system' => true,
            ],
            [
                'nom_type' => 'Achat',
                'description' => 'Achat d\'un animal',
                'categorie' => 'MOUVEMENT',
                'is_system' => true,
            ],
            [
                'nom_type' => 'Transfert',
                'description' => 'Transfert d\'un animal vers une autre ferme',
                'categorie' => 'MOUVEMENT',
                'is_system' => true,
            ],
            [
                'nom_type' => 'Décès',
                'description' => 'Décès d\'un animal',
                'categorie' => 'MOUVEMENT',
                'is_system' => true,
            ],
            [
                'nom_type' => 'Perte',
                'description' => 'Perte ou disparition d\'un animal',
                'categorie' => 'MOUVEMENT',
                'is_system' => true,
            ],
            [
                'nom_type' => 'Abattage',
                'description' => 'Abattage d\'un animal',
                'categorie' => 'MOUVEMENT',
                'is_system' => true,
            ],
            [
                'nom_type' => 'Contrôle',
                'description' => 'Contrôle sanitaire régulier',
                'categorie' => 'SANITAIRE',
                'is_system' => true,
            ],
            [
                'nom_type' => 'Pesée',
                'description' => 'Pesée de l\'animal',
                'categorie' => 'SANITAIRE',
                'is_system' => true,
            ],
            [
                'nom_type' => 'Autre',
                'description' => 'Autre type d\'événement',
                'categorie' => 'SANITAIRE',
                'is_system' => true,
            ],
        ];

        foreach ($types as $type) {
            TypeEvenement::firstOrCreate(
                ['nom_type' => $type['nom_type']],
                [
                    'description' => $type['description'],
                    'categorie' => $type['categorie'],
                    'is_system' => $type['is_system'],
                ]
            );
        }
    }
}
