<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ActivityLogService
{
    /**
     * Log an activity
     *
     * @param string $action created, updated, deleted, restored
     * @param Model $model The model instance
     * @param array|null $oldValues Data before modification
     * @param array|null $newValues Data after modification
     * @return void
     */
    public function log(string $action, Model $model, ?array $oldValues = null, ?array $newValues = null): void
    {
        try {
            $userId = Auth::id();
            
            // Essayer de récupérer farm_id depuis plusieurs sources
            $farmId = $this->resolveFarmId($model);
            
            $modelType = class_basename($model);
            $modelId = $model->getKey();
            
            $description = $this->generateDescription($action, $modelType, $model);
            
            ActivityLog::create([
                'user_id' => $userId,
                'farm_id' => $farmId,
                'action' => $action,
                'model_type' => $modelType,
                'model_id' => $modelId,
                'description' => $description,
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'ip_address' => Request::ip(),
            ]);
        } catch (\Exception $e) {
            // Never throw exceptions - logging failure should not break the main operation
            // Optionally log to Laravel's log file for debugging
            \Log::error('Activity log failed: ' . $e->getMessage());
        }
    }

    /**
     * Resolve farm_id from multiple sources
     * Priority: Model > Header > Session > null
     */
    private function resolveFarmId(Model $model): ?string
    {
        // 1. Essayer depuis le modèle (si le modèle a une propriété farm_id)
        if (isset($model->farm_id) && !empty($model->farm_id)) {
            return (string) $model->farm_id;
        }

        // 2. Essayer depuis le header X-Farm-ID
        $headerFarmId = Request::header('X-Farm-ID');
        if ($headerFarmId && !empty($headerFarmId)) {
            return $headerFarmId;
        }

        // 3. Essayer depuis la session (pour admin)
        $sessionFarmId = session('current_farm_id');
        if ($sessionFarmId && !empty($sessionFarmId)) {
            return $sessionFarmId;
        }

        // 4. Retourner null pour les activités globales (création ferme, utilisateur, etc.)
        return null;
    }

    /**
     * Generate a human-readable description
     */
    private function generateDescription(string $action, string $modelType, Model $model): string
    {
        $actionLabels = [
            'created' => 'Création',
            'updated' => 'Modification',
            'deleted' => 'Suppression',
            'restored' => 'Restauration',
        ];
        
        $actionLabel = $actionLabels[$action] ?? $action;
        
        // Try to get a readable identifier from the model
        $identifier = $this->getModelIdentifier($model);
        
        return "{$actionLabel} de {$modelType}" . ($identifier ? " : {$identifier}" : '');
    }

    /**
     * Get a readable identifier for the model
     */
    private function getModelIdentifier(Model $model): string
    {
        // Try common identifier fields
        $fields = ['name', 'nom', 'nom_type', 'numero_identification', 'numero'];
        
        foreach ($fields as $field) {
            if (isset($model->$field) && !empty($model->$field)) {
                return $model->$field;
            }
        }
        
        // Fallback to ID
        return $model->getKey();
    }

    /**
     * Get activity logs for a farm with optional filters
     * Includes global logs (farm_id = null) if requested
     *
     * @param string|null $farmId
     * @param array $filters action, model_type, user_id, date_debut, date_fin, per_page, include_global
     * @return LengthAwarePaginator
     */
    public function getForFarm(?string $farmId, array $filters = []): LengthAwarePaginator
    {
        $query = ActivityLog::query();
        
        // Si farm_id est fourni, filtrer par ferme
        if ($farmId) {
            $query->where('farm_id', $farmId);
        }
        
        // Inclure les logs globaux si demandé
        if (!empty($filters['include_global'])) {
            if ($farmId) {
                // Si on a un farm_id, inclure aussi les logs globaux
                $query->where(function($q) use ($farmId) {
                    $q->where('farm_id', $farmId)
                      ->orWhereNull('farm_id');
                });
            } else {
                // Si pas de farm_id, ne montrer que les logs globaux
                $query->whereNull('farm_id');
            }
        }
        
        // Apply filters
        if (!empty($filters['action'])) {
            $query->parAction($filters['action']);
        }
        
        if (!empty($filters['model_type'])) {
            $query->parModele($filters['model_type']);
        }
        
        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        
        if (!empty($filters['date_debut']) || !empty($filters['date_fin'])) {
            $query->periode($filters['date_debut'] ?? null, $filters['date_fin'] ?? null);
        }
        
        // Order by most recent first
        $query->orderBy('created_at', 'desc');
        
        $perPage = $filters['per_page'] ?? 15;
        
        return $query->paginate($perPage);
    }

    /**
     * Get global activity logs (not associated with a specific farm)
     *
     * @param array $filters action, model_type, user_id, date_debut, date_fin, per_page
     * @return LengthAwarePaginator
     */
    public function getGlobal(array $filters = []): LengthAwarePaginator
    {
        $filters['include_global'] = true;
        return $this->getForFarm(null, $filters);
    }
}
