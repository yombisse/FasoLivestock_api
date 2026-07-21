<?php

namespace Database\Seeders;

use App\Models\Categorie;
use App\Enums\CategorieSysteme;
use Illuminate\Database\Seeder;

class CategorieSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            CategorieSysteme::VENTE_ANIMAUX,
            CategorieSysteme::ACHAT_ANIMAUX,
            CategorieSysteme::SANTE_VETERINAIRE,
            CategorieSysteme::REPRODUCTION,
            CategorieSysteme::FRAIS_SANITAIRE,
            CategorieSysteme::FRAIS_REPRODUCTION,
            CategorieSysteme::FRAIS_MALADIE,
        ];

        foreach ($categories as $categoryEnum) {
            Categorie::updateOrCreate(
                ['id' => $categoryEnum->value],
                [
                    'nom_categorie' => $categoryEnum->getNom(),
                    'type' => $categoryEnum->getType(),
                    'description' => $categoryEnum->getDescription(),
                    'is_system' => true,
                    'sync_status' => 'synced',
                    'version' => 1,
                ]
            );
        }
    }
}
