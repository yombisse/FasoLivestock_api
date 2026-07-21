<?php

namespace App\Services\Sync;

use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class TransactionDeduplicationService
{
    /**
     * Générer la business key pour une transaction
     * Format: animal_id|evenement_id|date_transaction|type_transaction
     */
    public function generateBusinessKey(array $data): string
    {
        $date = isset($data['date_transaction']) 
            ? Carbon::parse($data['date_transaction'])->toDateString() 
            : null;

        return implode('|', [
            $data['animal_id'] ?? 'null',
            $data['evenement_id'] ?? 'null',
            $date ?? 'null',
            $data['type_transaction'] ?? 'null',
        ]);
    }

    /**
     * Rechercher un doublon par business key
     * CORRECTION CRITIQUE : Normalise les dates en UTC pour éviter les problèmes de fuseau horaire
     */
    public function findDuplicate(array $data, ?string $excludeId = null): ?Transaction
    {
        if (!isset($data['animal_id']) || !isset($data['evenement_id']) || !isset($data['date_transaction'])) {
            return null;
        }

        // CORRECTION : Normaliser la date en UTC pour comparaison
        $normalizedDate = Carbon::parse($data['date_transaction'])->toDateString();

        $query = Transaction::where('animal_id', $data['animal_id'])
            ->where('evenement_id', $data['evenement_id'])
            ->where('date_transaction', $normalizedDate);

        if (isset($data['type_transaction'])) {
            $query->where('type_transaction', $data['type_transaction']);
        }

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->first();
    }

    /**
     * Déterminer si une transaction doit être fusionnée
     */
    public function shouldMerge(array $mobileData): bool
    {
        // Fusionner seulement si c'est une transaction liée à un événement
        return isset($mobileData['evenement_id']) 
            && isset($mobileData['animal_id'])
            && isset($mobileData['date_transaction']);
    }

    /**
     * Fusionner deux transactions avec priorité backend
     * CORRECTION CRITIQUE : Adopte l'ID mobile pour éviter les doublons WatermelonDB
     */
    public function merge(Transaction $backend, array $mobileData): Transaction
    {
        $oldBackendId = $backend->id;
        $mobileId = $mobileData['id'] ?? null;

        $mergedData = [
            'id' => $mobileId, // CORRECTION : Adopter l'ID mobile pour WatermelonDB
            'farm_id' => $backend->farm_id,
            'animal_id' => $backend->animal_id,
            'evenement_id' => $backend->evenement_id,
            'categorie_id' => $backend->categorie_id, // Priorité backend
            'montant' => $backend->montant, // Priorité backend
            'date_transaction' => $backend->date_transaction, // Priorité backend
            'description' => $backend->description ?? $mobileData['description'] ?? null, // Priorité backend
            'type_transaction' => $backend->type_transaction,
            'metadonnees' => $this->mergeMetadonnees($backend->metadonnees, $mobileData['metadonnees'] ?? null),
            'version' => max($backend->version, $mobileData['version'] ?? 0) + 1,
            'sync_status' => 'synced',
        ];

        // Conserver les champs optionnels du backend
        if (isset($backend->user_id)) {
            $mergedData['user_id'] = $backend->user_id;
        }
        if (isset($backend->numero_transaction)) {
            $mergedData['numero_transaction'] = $backend->numero_transaction;
        }
        if (isset($backend->tiers)) {
            $mergedData['tiers'] = $backend->tiers;
        }

        // Supprimer l'ancien enregistrement avec l'ID backend
        $backend->delete();

        // Créer/Mettre à jour avec l'ID mobile
        $transaction = Transaction::updateOrCreate(
            ['id' => $mobileId],
            $mergedData
        );

        Log::info('Transaction merged (ID mobile adopted)', [
            'old_backend_id' => $oldBackendId,
            'new_mobile_id' => $mobileId,
            'business_key' => $this->generateBusinessKey($mergedData),
            'version' => $mergedData['version'],
        ]);

        return $transaction->fresh();
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
     * Vérifier si deux dates sont considérées comme identiques (tolérance 24h)
     */
    public function isSameDate($date1, $date2): bool
    {
        if (!$date1 || !$date2) {
            return false;
        }

        $d1 = Carbon::parse($date1);
        $d2 = Carbon::parse($date2);

        // Même jour
        if ($d1->toDateString() === $d2->toDateString()) {
            return true;
        }

        // Tolérance 24h
        return $d1->diffInHours($d2) < 24;
    }
}
