<?php

namespace Database\Seeders;

use App\Models\TypeEvenement;
use App\Enums\TypeEvenementSysteme;
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
            TypeEvenementSysteme::CHALEUR,
            TypeEvenementSysteme::SAILLIE,
            TypeEvenementSysteme::GESTATION,
            TypeEvenementSysteme::MISE_BAS,
            TypeEvenementSysteme::NAISSANCE,
            TypeEvenementSysteme::VACCINATION,
            TypeEvenementSysteme::TRAITEMENT,
            TypeEvenementSysteme::MALADIE,
            TypeEvenementSysteme::VENTE,
            TypeEvenementSysteme::ACHAT,
            TypeEvenementSysteme::TRANSFERT,
            TypeEvenementSysteme::DECES,
            TypeEvenementSysteme::PERTE,
            TypeEvenementSysteme::ABATTAGE,
            TypeEvenementSysteme::CONTROLE,
            TypeEvenementSysteme::PESSEE,
            TypeEvenementSysteme::AUTRE,
        ];

        foreach ($types as $typeEnum) {
            TypeEvenement::firstOrCreate(
                ['id' => $typeEnum->value],
                [
                    'nom_type' => $typeEnum->getNom(),
                    'description' => $this->getDescription($typeEnum),
                    'categorie' => $typeEnum->getCategorie(),
                    'is_system' => true,
                ]
            );
        }
    }

    /**
     * Obtenir la description pour un type d'événement
     */
    private function getDescription(TypeEvenementSysteme $type): string
    {
        return match($type) {
            TypeEvenementSysteme::CHALEUR => 'Détection de chaleur chez l\'animal femelle',
            TypeEvenementSysteme::SAILLIE => 'Accouplement ou insémination de l\'animal',
            TypeEvenementSysteme::GESTATION => 'Gestation de l\'animal femelle',
            TypeEvenementSysteme::MISE_BAS => 'Naissance des petits',
            TypeEvenementSysteme::NAISSANCE => 'Naissance d\'un animal',
            TypeEvenementSysteme::VACCINATION => 'Vaccination préventive ou curative',
            TypeEvenementSysteme::TRAITEMENT => 'Traitement médical ou vétérinaire',
            TypeEvenementSysteme::MALADIE => 'Déclaration de maladie chez l\'animal',
            TypeEvenementSysteme::VENTE => 'Vente d\'un animal',
            TypeEvenementSysteme::ACHAT => 'Achat d\'un animal',
            TypeEvenementSysteme::TRANSFERT => 'Transfert d\'un animal vers une autre ferme',
            TypeEvenementSysteme::DECES => 'Décès d\'un animal',
            TypeEvenementSysteme::PERTE => 'Perte ou disparition d\'un animal',
            TypeEvenementSysteme::ABATTAGE => 'Abattage d\'un animal',
            TypeEvenementSysteme::CONTROLE => 'Contrôle sanitaire régulier',
            TypeEvenementSysteme::PESSEE => 'Pesée de l\'animal',
            TypeEvenementSysteme::AUTRE => 'Autre type d\'événement',
        };
    }
}
