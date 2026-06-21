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
            ],
            [
                'nom_type' => 'Saillie',
                'description' => 'Accouplement ou insémination de l\'animal',
            ],
            [
                'nom_type' => 'Gestation confirmée',
                'description' => 'Confirmation de la gestation par examen vétérinaire',
            ],
            [
                'nom_type' => 'Mise bas',
                'description' => 'Naissance des petits',
            ],
            [
                'nom_type' => 'Vaccination',
                'description' => 'Vaccination préventive ou curative',
            ],
            [
                'nom_type' => 'Traitement',
                'description' => 'Traitement médical ou vétérinaire',
            ],
            [
                'nom_type' => 'Vente',
                'description' => 'Vente d\'un animal',
            ],
            [
                'nom_type' => 'Achat',
                'description' => 'Achat d\'un animal',
            ],
            [
                'nom_type' => 'Transfert',
                'description' => 'Transfert d\'un animal vers une autre ferme',
            ],
            [
                'nom_type' => 'Décès',
                'description' => 'Décès d\'un animal',
            ],
            [
                'nom_type' => 'Perte',
                'description' => 'Perte ou disparition d\'un animal',
            ],
            [
                'nom_type' => 'Abattage',
                'description' => 'Abattage d\'un animal',
            ],
            [
                'nom_type' => 'Contrôle',
                'description' => 'Contrôle sanitaire régulier',
            ],
            [
                'nom_type' => 'Pesée',
                'description' => 'Pesée de l\'animal',
            ],
            [
                'nom_type' => 'Autre',
                'description' => 'Autre type d\'événement',
            ],
        ];

        foreach ($types as $type) {
            TypeEvenement::firstOrCreate(
                ['nom_type' => $type['nom_type']],
                ['description' => $type['description']]
            );
        }
    }
}
