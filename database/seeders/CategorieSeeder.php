<?php

namespace Database\Seeders;

use App\Models\Categorie;
use Illuminate\Database\Seeder;

class CategorieSeeder extends Seeder
{
    public function run(): void
    {
        // REVENU
        Categorie::firstOrCreate(
            ['nom_categorie' => 'Vente d\'animaux'],
            [
                'type' => 'REVENU',
                'description' => 'Revenus provenant de la vente d\'animaux',
                'sync_status' => 'synced',
                'version' => 1,
            ]
        );

        

        // DEPENSE
        Categorie::firstOrCreate(
            ['nom_categorie' => 'Achat d\'animaux'],
            [
                'type' => 'DEPENSE',
                'description' => 'Dépenses pour l\'achat d\'animaux',
                'sync_status' => 'synced',
                'version' => 1,
            ]
        );

        Categorie::firstOrCreate(
            ['nom_categorie' => 'Santé (vétérinaire)'],
            [
                'type' => 'DEPENSE',
                'description' => 'Frais vétérinaires, vaccins et traitements',
                'sync_status' => 'synced',
                'version' => 1,
            ]
        );

        Categorie::firstOrCreate(
            ['nom_categorie' => 'Reproduction'],
            [
                'type' => 'DEPENSE',
                'description' => 'Dépenses liées à la reproduction (insémination, saillie)',
                'sync_status' => 'synced',
                'version' => 1,
            ]
        );

        // Catégories pour événements automatiques (créées par EvenementTransactionService)
        Categorie::firstOrCreate(
            ['nom_categorie' => 'FRAIS_SANITAIRE'],
            [
                'type' => 'DEPENSE',
                'description' => 'Frais sanitaires générés automatiquement (vaccinations, traitements, consultations)',
                'sync_status' => 'synced',
                'version' => 1,
            ]
        );

        Categorie::firstOrCreate(
            ['nom_categorie' => 'FRAIS_REPRODUCTION'],
            [
                'type' => 'DEPENSE',
                'description' => 'Frais de reproduction générés automatiquement (saillie, insémination, gestation, mise bas)',
                'sync_status' => 'synced',
                'version' => 1,
            ]
        );

        Categorie::firstOrCreate(
            ['nom_categorie' => 'FRAIS_MALADIE'],
            [
                'type' => 'DEPENSE',
                'description' => 'Frais liés aux maladies et diagnostics',
                'sync_status' => 'synced',
                'version' => 1,
            ]
        );
    }
}
