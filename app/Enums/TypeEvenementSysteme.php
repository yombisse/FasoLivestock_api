<?php

namespace App\Enums;

enum TypeEvenementSysteme: string
{
    // Reproduction
    case CHALEUR = '1owjZUIVP0gIrAcSq8Sw';
    case SAILLIE = 'JKAWN9rN0BYVE46snec1';
    case GESTATION = 'HkGB81kowtR76VdLZqZO';
    case MISE_BAS = 'kHe1MGrLuLxAwZdSQdrY';
    case NAISSANCE = 'JEDpdHtycshklyWggT4B';

    // Sanitaire
    case VACCINATION = 'u4OJvVlCnLQ7H7xcIVMc';
    case TRAITEMENT = 'BfLtxah0PEx4CJn5zQ50';
    case MALADIE = '5XjK3qZ8pLmN9oR2sT4v';
    case CONTROLE = 'BtFDz672cHmbwNH4GaiH';
    case PESSEE = 'rtpISsZNMYM6It03Xl2g';
    case AUTRE = 'oNZ8XZX15yKUpDyl78AF';

    // Mouvement
    case VENTE = 'Khjy61rPsSByYjRDE6EL';
    case ACHAT = 'f96dSuy6ocGMWX58R6ve';
    case TRANSFERT = '02amTPryEc4apSmqQkoc';
    case DECES = 'vhSYzGylJQAmfiVrgbjp';
    case PERTE = 'A36SgYEfEIdgwujVpt0m';
    case ABATTAGE = 'P9Tczk0VV2AwG9P68Fpl';

    /**
     * Obtenir tous les cas de reproduction
     */
    public static function reproduction(): array
    {
        return [
            self::CHALEUR,
            self::SAILLIE,
            self::GESTATION,
            self::MISE_BAS,
            self::NAISSANCE,
        ];
    }

    /**
     * Obtenir tous les cas sanitaires
     */
    public static function sanitaire(): array
    {
        return [
            self::VACCINATION,
            self::TRAITEMENT,
            self::MALADIE,
            self::CONTROLE,
            self::PESSEE,
            self::AUTRE,
        ];
    }

    /**
     * Obtenir tous les cas de mouvement
     */
    public static function mouvement(): array
    {
        return [
            self::VENTE,
            self::ACHAT,
            self::TRANSFERT,
            self::DECES,
            self::PERTE,
            self::ABATTAGE,
        ];
    }

    /**
     * Obtenir le nom lisible du type
     */
    public function getNom(): string
    {
        return match($this) {
            self::CHALEUR => 'Chaleur',
            self::SAILLIE => 'Saillie',
            self::GESTATION => 'Gestation',
            self::MISE_BAS => 'Mise bas',
            self::NAISSANCE => 'NAISSANCE',
            self::VACCINATION => 'Vaccination',
            self::TRAITEMENT => 'Traitement',
            self::MALADIE => 'Maladie',
            self::CONTROLE => 'Contrôle',
            self::PESSEE => 'Pesée',
            self::AUTRE => 'Autre',
            self::VENTE => 'Vente',
            self::ACHAT => 'Achat',
            self::TRANSFERT => 'Transfert',
            self::DECES => 'Décès',
            self::PERTE => 'Perte',
            self::ABATTAGE => 'Abattage',
        };
    }

    /**
     * Obtenir la catégorie du type
     */
    public function getCategorie(): string
    {
        return match($this) {
            self::CHALEUR, self::SAILLIE, self::GESTATION,
            self::MISE_BAS, self::NAISSANCE => 'REPRODUCTION',
            self::VACCINATION, self::TRAITEMENT, self::MALADIE,
            self::CONTROLE, self::PESSEE, self::AUTRE => 'SANITAIRE',
            self::VENTE, self::ACHAT, self::TRANSFERT,
            self::DECES, self::PERTE, self::ABATTAGE => 'MOUVEMENT',
        };
    }
}
