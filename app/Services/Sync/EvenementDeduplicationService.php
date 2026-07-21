<?php

namespace App\Services\Sync;

use App\Models\Evenement;
use App\Enums\TypeEvenementSysteme;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class EvenementDeduplicationService
{
    /**
     * Types d'événements critiques nécessitant une déduplication
     * Un animal ne peut pas subir ces événements deux fois le même jour
     */
    private const CRITICAL_EVENT_TYPES = [
        TypeEvenementSysteme::SAILLIE->value,
        TypeEvenementSysteme::GESTATION->value,
        TypeEvenementSysteme::DECES->value,
        TypeEvenementSysteme::MISE_BAS->value,
    ];

    /**
     * Déterminer si un événement doit être soumis à la déduplication
     */
    public function shouldDeduplicate(array $data): bool
    {
        if (!isset($data['type_evenement_id'])) {
            return false;
        }

        return in_array($data['type_evenement_id'], self::CRITICAL_EVENT_TYPES);
    }

    /**
     * Rechercher un doublon par business key
     * Clé métier : animal_id + type_evenement_id + date_evenement
     */
    public function findDuplicate(array $data, ?string $excludeId = null): ?Evenement
    {
        if (!isset($data['animal_id']) || !isset($data['type_evenement_id']) || !isset($data['date_evenement'])) {
            return null;
        }

        // Normaliser la date en UTC pour comparaison
        $normalizedDate = Carbon::parse($data['date_evenement'])->toDateString();

        $query = Evenement::where('animal_id', $data['animal_id'])
            ->where('type_evenement_id', $data['type_evenement_id'])
            ->where('date_evenement', $normalizedDate);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->first();
    }

    /**
     * Fusionner deux événements avec priorité backend
     * Adopte l'ID mobile pour éviter les doublons WatermelonDB
     */
    public function merge(Evenement $backend, array $mobileData): Evenement
    {
        $oldBackendId = $backend->id;
        $mobileId = $mobileData['id'] ?? null;

        $mergedData = [
            'id' => $mobileId, // Adopter l'ID mobile pour WatermelonDB
            'farm_id' => $backend->farm_id,
            'animal_id' => $backend->animal_id,
            'type_evenement_id' => $backend->type_evenement_id,
            'date_evenement' => $backend->date_evenement, // Priorité backend
            'description' => $backend->description ?? $mobileData['description'] ?? null, // Priorité backend
            'cout' => $backend->cout ?? $mobileData['cout'] ?? null, // Priorité backend
            'statut_avant' => $backend->statut_avant ?? $mobileData['statut_avant'] ?? null,
            'statut_apres' => $backend->statut_apres ?? $mobileData['statut_apres'] ?? null,
            'farm_destination_id' => $backend->farm_destination_id ?? $mobileData['farm_destination_id'] ?? null,
            'evenement_id' => $backend->evenement_id ?? $mobileData['evenement_id'] ?? null,
            'sync_status' => 'synced',
        ];

        // Conserver les métadonnées si présentes
        if (isset($backend->metadonnees) || isset($mobileData['metadonnees'])) {
            $mergedData['metadonnees'] = $this->mergeMetadonnees(
                $backend->metadonnees ?? null,
                $mobileData['metadonnees'] ?? null
            );
        }

        // Supprimer l'ancien enregistrement avec l'ID backend
        $backend->delete();

        // Créer/Mettre à jour avec l'ID mobile
        $evenement = Evenement::updateOrCreate(
            ['id' => $mobileId],
            $mergedData
        );

        Log::info('Evenement merged (ID mobile adopted)', [
            'old_backend_id' => $oldBackendId,
            'new_mobile_id' => $mobileId,
            'type_evenement_id' => $backend->type_evenement_id,
            'animal_id' => $backend->animal_id,
            'date_evenement' => $backend->date_evenement,
        ]);

        return $evenement->fresh();
    }

    /**
     * Fusionner les métadonnées
     */
    private function mergeMetadonnees(?array $backendMeta, ?array $mobileMeta): array
    {
        $backendMeta = $backendMeta ?? [];
        $mobileMeta = $mobileMeta ?? [];

        // Fusionner avec priorité aux métadonnées mobile personnalisées
        return array_merge($backendMeta, $mobileMeta);
    }

    /**
     * Générer la business key pour un événement
     * Format: animal_id|type_evenement_id|date_evenement
     */
    public function generateBusinessKey(array $data): string
    {
        $date = isset($data['date_evenement'])
            ? Carbon::parse($data['date_evenement'])->toDateString()
            : null;

        return implode('|', [
            $data['animal_id'] ?? 'null',
            $data['type_evenement_id'] ?? 'null',
            $date ?? 'null',
        ]);
    }
}
