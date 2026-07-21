<?php

namespace Database\Seeders;

use App\Models\Espece;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

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
            [
                'nom' => 'Canidé',
                'description' => 'Chiens et autres canidés',
            ],
        ];

        foreach ($especes as $espece) {
            $existing = Espece::where('nom', $espece['nom'])->first();

            if ($existing) {
                // Ne pas modifier l'ID si l'espèce existe déjà
                $existing->update([
                    'description' => $espece['description'],
                ]);
            } else {
                // Créer avec un nouvel ID seulement si c'est une nouvelle espèce
                Espece::create([
                    'id' => substr(Str::random(20), 0, 20),
                    'nom' => $espece['nom'],
                    'description' => $espece['description'],
                ]);
            }
        }

        $this->command->info('✅ Espèces préremplies avec succès.');
    }
}
