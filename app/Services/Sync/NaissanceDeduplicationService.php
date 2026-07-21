<?php

namespace App\Services\Sync;

use App\Models\Naissance;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class NaissanceDeduplicationService
{
    /**
     * Rechercher un doublon par business key
     * Clé métier : mere_id + date_naissance
     * Une mère ne peut pas mettre bas deux fois le même jour
     */
    public function findDuplicate(array $data, ?string $excludeId = null): ?Naissance
    {
        if (!isset($data['mother_id']) || !isset($data['date_naissance'])) {
            return null;
        }

        // Normaliser la date en UTC pour comparaison
        $normalizedDate = Carbon::parse($data['date_naissance'])->toDateString();

        $query = Naissance::where('mother_id', $data['mother_id'])
            ->where('date_naissance', $normalizedDate);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->first();
    }

    /**
     * Fusionner deux naissances avec priorité backend
     * Adopte l'ID mobile pour éviter les doublons WatermelonDB
     */
    public function merge(Naissance $backend, array $mobileData): Naissance
    {
        $oldBackendId = $backend->id;
        $mobileId = $mobileData['id'] ?? null;

        $mergedData = [
            'id' => $mobileId, // Adopter l'ID mobile pour WatermelonDB
            'farm_id' => $backend->farm_id,
            'mother_id' => $backend->mother_id,
            'date_naissance' => $backend->date_naissance, // Priorité backend
            'nombre_petits' => $backend->nombre_petits, // Priorité backend (calcul précis)
            'poids_naissance' => $backend->poids_naissance ?? $mobileData['poids_naissance'] ?? null,
            'observation' => $backend->observation ?? $mobileData['observation'] ?? null, // Priorité backend
            'evenement_id' => $backend->evenement_id ?? $mobileData['evenement_id'] ?? null,
            'sync_status' => 'synced',
        ];

        // Fusionner les métadonnées si présentes
        if (isset($backend->metadonnees) || isset($mobileData['metadonnees'])) {
            $mergedData['metadonnees'] = $this->mergeMetadonnees(
                $backend->metadonnees ?? null,
                $mobileData['metadonnees'] ?? null
            );
        }

        // Supprimer l'ancien enregistrement avec l'ID backend
        $backend->delete();

        // Créer/Mettre à jour avec l'ID mobile
        $naissance = Naissance::updateOrCreate(
            ['id' => $mobileId],
            $mergedData
        );

        Log::info('Naissance merged (ID mobile adopted)', [
            'old_backend_id' => $oldBackendId,
            'new_mobile_id' => $mobileId,
            'mother_id' => $backend->mother_id,
            'date_naissance' => $backend->date_naissance,
            'nombre_petits' => $backend->nombre_petits,
        ]);

        return $naissance->fresh();
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
     * Générer la business key pour une naissance
     * Format: mere_id|date_naissance
     */
    public function generateBusinessKey(array $data): string
    {
        $date = isset($data['date_naissance'])
            ? Carbon::parse($data['date_naissance'])->toDateString()
            : null;

        return implode('|', [
            $data['mother_id'] ?? 'null',
            $date ?? 'null',
        ]);
    }
}
