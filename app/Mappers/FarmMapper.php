<?php

namespace App\Mappers;

/**
 * FarmMapper - Normalise les données API pour l'affichage Blade
 * 
 * Responsabilités :
 * - Transformer les données API en format UI cohérent
 * - Uniformiser les clés (ex: name, location, animals_count)
 * - Gérer les champs dérivés (ex: status)
 * 
 * Règle : Le Blade ne doit jamais dépendre de la structure API brute
 */
class FarmMapper
{
    /**
     * Transforme les données API en format normalisé pour Blade
     * 
     * @param array $apiData Données brutes retournées par l'API
     * @return array Données normalisées pour Blade
     */
    public static function toView(array $apiData): array
    {
        // Défensif : éviter des Undefined Index
        return [
            'id' => $apiData['id'] ?? null,
            'name' => $apiData['name'] ?? 'Non défini',
            'location' => $apiData['location'] ?? 'Non défini',
            'description' => $apiData['description'] ?? '',
            'type_elevage' => $apiData['type_elevage'] ?? 'Non défini',
            'photo' => $apiData['photo'] ?? null,
            'status' => self::normalizeStatus($apiData),
            'animals_count' => $apiData['animals_count'] ?? 0,
            'users_count' => $apiData['users_count'] ?? 0,
            'lots_count' => $apiData['lots_count'] ?? 0,
            'owner' => $apiData['owner'] ?? null,
            'users' => self::normalizeUsers($apiData['users'] ?? []),
            'created_at' => $apiData['created_at'] ?? null,
            'updated_at' => $apiData['updated_at'] ?? null,
            'deleted_at' => $apiData['deleted_at'] ?? null,
        ];
    }

    /**
     * Normalise le statut (champ dérivé)
     */
    private static function normalizeStatus(array $apiData): string
    {
        return isset($apiData['deleted_at']) && !empty($apiData['deleted_at'])
            ? 'ARCHIVÉE'
            : 'ACTIF';
    }

    /**
     * Normalise la liste des utilisateurs
     */
    private static function normalizeUsers(array $users): array
    {
        return array_map(function ($user) {
            return [
                'id' => $user['id'] ?? null,
                'name' => $user['name'] ?? 'Non défini',
                'email' => $user['email'] ?? '—',
                'role' => $user['role'] ?? 'worker',
                'pivot' => [
                    'role' => $user['role'] ?? 'worker',
                ],
            ];
        }, $users);
    }

    /**
     * Transforme une liste de fermes (pour index)
     * 
     * @param array $apiData Données API avec clé 'farms' et 'meta'
     * @return array Données normalisées pour Blade
     */
    public static function toCollectionView(array $apiData): array
    {
        $farms = $apiData['farms'] ?? [];
        $meta = $apiData['meta'] ?? [];

        return [
            'farms' => array_map(fn ($farm) => self::toView($farm), $farms),
            'meta' => $meta,
        ];
    }
}
