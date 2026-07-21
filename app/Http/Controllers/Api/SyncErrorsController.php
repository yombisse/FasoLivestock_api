<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Animal;
use App\Models\Transaction;
use App\Models\Evenement;
use App\Models\Lot;
use App\Models\Notification;
use App\Models\Naissance;
use App\Models\Espece;
use App\Models\Categorie;
use App\Models\TypeEvenement;
use App\Models\Farm;

class SyncErrorsController extends Controller
{
    /**
     * Get sync errors filtered by module and farm
     * Returns errors from sync_error_logs table
     */
    public function getSyncErrors(Request $request)
    {
        try {
            $request->validate([
                'module' => 'nullable|string|in:evenements,transactions,naissances,notifications,animals,lots,especes,categories,type_evenements,farms',
                'farm_id' => 'nullable|string|min:16|max:20',
            ]);

            $userId = $request->user()->id;
            $module = $request->query('module');
            $farmId = $request->query('farm_id');

            $query = \DB::table('sync_error_logs');

            // Filter by module if provided
            if ($module) {
                $query->where('module', $module);
            }

            // Filter by farm_id if provided
            if ($farmId) {
                // Verify user has access to this farm
                $farm = \App\Models\Farm::where('id', $farmId)
                    ->where(function ($query) use ($userId) {
                        $query->where('owner_id', $userId)
                            ->orWhereHas('users', function ($q) use ($userId) {
                                $q->where('user_id', $userId);
                            });
                    })
                    ->first();

                if (!$farm) {
                    return ApiResponse::error('Accès non autorisé à cette ferme', null, 403);
                }

                $query->where('farm_id', $farmId);
            } else {
                // If no farm_id specified, only show errors for farms the user has access to
                $accessibleFarmIds = \App\Models\Farm::where(function ($query) use ($userId) {
                        $query->where('owner_id', $userId)
                            ->orWhereHas('users', function ($q) use ($userId) {
                                $q->where('user_id', $userId);
                            });
                    })
                    ->pluck('id')
                    ->toArray();

                $query->whereIn('farm_id', $accessibleFarmIds);
            }

            // Order by occurred_at desc (most recent first)
            $query->orderBy('occurred_at', 'desc');

            // Paginate results (default 50 per page)
            $perPage = $request->query('per_page', 50);
            $errors = $query->paginate($perPage);

            return ApiResponse::success([
                'errors' => $errors->items(),
                'pagination' => [
                    'total' => $errors->total(),
                    'per_page' => $errors->perPage(),
                    'current_page' => $errors->currentPage(),
                    'last_page' => $errors->lastPage(),
                ],
            ], 'Erreurs de sync récupérées avec succès');
        } catch (\Exception $e) {
            Log::error('SYNC/ERRORS - Failed to retrieve sync errors', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return ApiResponse::error($e->getMessage(), null, 400);
        }
    }

    /**
     * Endpoint de vérification de cohérence pour le mobile
     * Permet au mobile de vérifier quels enregistrements locaux sont désynchronisés
     */
    public function verifyConsistency(Request $request)
    {
        try {
            $request->validate([
                'farm_id' => 'required|string|min:16|max:20',
                'local_ids' => 'required|array',
                'local_ids.*.table' => 'required|string',
                'local_ids.*.id' => 'required|string',
            ]);

            $userId = $request->user()->id;
            $farmId = $request->farm_id;
            $localIds = $request->local_ids;

            // Verify user has access to this farm
            $farm = \App\Models\Farm::where('id', $farmId)
                ->where(function ($query) use ($userId) {
                    $query->where('owner_id', $userId)
                        ->orWhereHas('users', function ($q) use ($userId) {
                            $q->where('user_id', $userId);
                        });
                })
                ->first();

            if (!$farm) {
                return ApiResponse::error('Accès non autorisé à cette ferme', null, 403);
            }

            $inconsistentRecords = [];

            foreach ($localIds as $item) {
                $table = $item['table'];
                $id = $item['id'];
                $modelClass = $this->getModelClass($table);

                if ($modelClass) {
                    $exists = $modelClass::withTrashed()->where('id', $id)->exists();
                    if (!$exists) {
                        $inconsistentRecords[] = [
                            'table' => $table,
                            'id' => $id,
                            'reason' => 'not_found_on_server',
                        ];
                    }
                }
            }

            return ApiResponse::success([
                'inconsistent_records' => $inconsistentRecords,
                'total_inconsistent' => count($inconsistentRecords),
            ], 'Vérification de cohérence terminée');
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), null, 400);
        }
    }

    /**
     * Obtenir la classe modèle pour une table
     */
    private function getModelClass($table)
    {
        $modelMap = [
            'animals' => Animal::class,
            'transactions' => Transaction::class,
            'evenements' => Evenement::class,
            'lots' => Lot::class,
            'notifications' => Notification::class,
            'naissances' => Naissance::class,
            'especes' => Espece::class,
            'categories' => Categorie::class,
            'type_evenements' => TypeEvenement::class,
            'farms' => Farm::class,
        ];

        return $modelMap[$table] ?? null;
    }
}
