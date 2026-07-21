<?php

namespace App\Enums;

enum CategorieSysteme: string
{
    // Revenus
    case VENTE_ANIMAUX = 'RrUhgJmYpeIPc5h6aHpf';

    // Dépenses
    case ACHAT_ANIMAUX = 'LteIMADnrgkNQmtwB7Gr';
    case SANTE_VETERINAIRE = 'NHqlims4Zh7tOzqkqMJ3';
    case REPRODUCTION = '9TxearV5HeeHWNyoasKn';

    // Catégories générées automatiquement
    case FRAIS_SANITAIRE = 'SUdSyL5K0Mej3eNZFvsT';
    case FRAIS_REPRODUCTION = 'Qhj7A8iKPc5jxefXcl3s';
    case FRAIS_MALADIE = '9ilavW5aWVE0cfYZ3CZC';

    /**
     * Obtenir toutes les catégories de revenus
     */
    public static function revenus(): array
    {
        return [
            self::VENTE_ANIMAUX,
        ];
    }

    /**
     * Obtenir toutes les catégories de dépenses
     */
    public static function depenses(): array
    {
        return [
            self::ACHAT_ANIMAUX,
            self::SANTE_VETERINAIRE,
            self::REPRODUCTION,
            self::FRAIS_SANITAIRE,
            self::FRAIS_REPRODUCTION,
            self::FRAIS_MALADIE,
        ];
    }

    /**
     * Obtenir les catégories générées automatiquement
     */
    public static function automatiques(): array
    {
        return [
            self::FRAIS_SANITAIRE,
            self::FRAIS_REPRODUCTION,
            self::FRAIS_MALADIE,
        ];
    }

    /**
     * Obtenir le nom lisible de la catégorie
     */
    public function getNom(): string
    {
        return match($this) {
            self::VENTE_ANIMAUX => 'Vente d\'animaux',
            self::ACHAT_ANIMAUX => 'Achat d\'animaux',
            self::SANTE_VETERINAIRE => 'Santé (vétérinaire)',
            self::REPRODUCTION => 'Reproduction',
            self::FRAIS_SANITAIRE => 'FRAIS_SANITAIRE',
            self::FRAIS_REPRODUCTION => 'FRAIS_REPRODUCTION',
            self::FRAIS_MALADIE => 'FRAIS_MALADIE',
        };
    }

    /**
     * Obtenir le type de la catégorie
     */
    public function getType(): string
    {
        return match($this) {
            self::VENTE_ANIMAUX => 'REVENU',
            self::ACHAT_ANIMAUX, self::SANTE_VETERINAIRE, self::REPRODUCTION,
            self::FRAIS_SANITAIRE, self::FRAIS_REPRODUCTION, self::FRAIS_MALADIE => 'DEPENSE',
        };
    }

    /**
     * Obtenir la description de la catégorie
     */
    public function getDescription(): string
    {
        return match($this) {
            self::VENTE_ANIMAUX => 'Revenus provenant de la vente d\'animaux',
            self::ACHAT_ANIMAUX => 'Dépenses pour l\'achat d\'animaux',
            self::SANTE_VETERINAIRE => 'Frais vétérinaires, vaccins et traitements',
            self::REPRODUCTION => 'Dépenses liées à la reproduction (insémination, saillie)',
            self::FRAIS_SANITAIRE => 'Frais sanitaires générés automatiquement (vaccinations, traitements, consultations)',
            self::FRAIS_REPRODUCTION => 'Frais de reproduction générés automatiquement (saillie, insémination, gestation, mise bas)',
            self::FRAIS_MALADIE => 'Frais liés aux maladies et diagnostics',
        };
    }
}
