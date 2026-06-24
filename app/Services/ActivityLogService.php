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
            $farmId = Request::header('X-Farm-ID');
            
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
     *
     * @param string $farmId
     * @param array $filters action, model_type, user_id, date_debut, date_fin, per_page
     * @return LengthAwarePaginator
     */
    public function getForFarm(string $farmId, array $filters = []): LengthAwarePaginator
    {
        $query = ActivityLog::query()->parFerme($farmId);
        
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
}
