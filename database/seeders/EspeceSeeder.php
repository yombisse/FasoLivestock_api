<?php

namespace Database\Seeders;

use App\Models\Espece;
use Illuminate\Database\Seeder;

class EspeceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $especes = [
            [
                'nom' => 'Bovin',
                'description' => 'Bovins domestiques (zébus, taureaux, vaches)',
            ],
            [
                'nom' => 'Ovin',
                'description' => 'Moutons et brebis',
            ],
            [
                'nom' => 'Caprin',
                'description' => 'Chèvres et boucs',
            ],
            [
                'nom' => 'Porcin',
                'description' => 'Porcs et truies',
            ],
            [
                'nom' => 'Volaille',
                'description' => 'Poulets, pintades, dindes, canards',
            ],
            [
                'nom' => 'Équin',
                'description' => 'Chevaux et poneys',
            ],
            [
                'nom' => 'Asin',
                'description' => 'Ânes et baudets',
            ],
            [
                'nom' => 'Camelin',
                'description' => 'Dromadaires',
            ],
            [
                'nom' => 'Lapin',
                'description' => 'Lapins d\'élevage',
            ],
        ];

        foreach ($especes as $espece) {
            Espece::updateOrCreate(
                ['nom' => $espece['nom']],
                [
                    'description' => $espece['description'],
                ]
            );
        }

        $this->command->info('✅ Espèces préremplies avec succès.');
    }
}
