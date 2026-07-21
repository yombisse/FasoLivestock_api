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
use Carbon\Carbon;

class SyncPullController extends Controller
{
    /**
     * Pull changes from server to mobile (WatermelonDB format)
     * Returns changes grouped by table with created/updated/deleted arrays
     */
    public function pull(Request $request)
    {
        try {
            // WatermelonDB sends 'last_pulled_at' but we also accept 'last_sync_at' for compatibility
            $lastPulledAt = $request->input('last_pulled_at') ?? $request->input('last_sync_at');
            
            $request->validate([
                'farm_id' => 'required|string|min:16|max:20',
                'schema_version' => 'nullable|integer',
                'last_pulled_at' => 'nullable|date',
                'last_sync_at' => 'nullable|date',
            ]);

            $userId = $request->user()->id;
            $farmId = $request->farm_id;
            
            // Handle null last_pulled_at (initial sync - return all data)
            $lastSyncAt = $lastPulledAt ? Carbon::parse($lastPulledAt) : Carbon::parse('1970-01-01');

            // Capture snapshot timestamp at the beginning for consistency
            $snapshotTime = now();

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

            // Helper function to categorize changes into created/updated/deleted
            $categorizeChanges = function ($records, $lastSyncAt, $snapshotTime) {
                $categorized = [
                    'created' => [],
                    'updated' => [],
                    'deleted' => [],
                ];

                foreach ($records as $record) {
                    // Convert record to array (handles both Eloquent models and stdClass objects)
                    if (is_array($record)) {
                        $recordArray = $record;
                    } elseif (method_exists($record, 'toArray')) {
                        $recordArray = $record->toArray();
                    } else {
                        $recordArray = (array) $record;
                    }
                    
                    // Remove business metadata (sync_status, version, etc.)
                    unset($recordArray['sync_status']);
                    unset($recordArray['last_modified_by']);
                    unset($recordArray['version']);
                    
                    // Keep only essential timestamps
                    $recordArray['created_at'] = $recordArray['created_at'] ?? null;
                    $recordArray['updated_at'] = $recordArray['updated_at'] ?? null;
                    $recordArray['deleted_at'] = $recordArray['deleted_at'] ?? null;

                    // Determine the category based on timestamps
                    if ($recordArray['deleted_at'] && 
                        Carbon::parse($recordArray['deleted_at'])->gt($lastSyncAt) && 
                        Carbon::parse($recordArray['deleted_at'])->lte($snapshotTime)) {
                        $categorized['deleted'][] = $recordArray;
                    } elseif (Carbon::parse($recordArray['created_at'])->gt($lastSyncAt) && 
                              Carbon::parse($recordArray['created_at'])->lte($snapshotTime)) {
                        $categorized['created'][] = $recordArray;
                    } elseif (Carbon::parse($recordArray['updated_at'])->gt($lastSyncAt) && 
                              Carbon::parse($recordArray['updated_at'])->lte($snapshotTime)) {
                        $categorized['updated'][] = $recordArray;
                    }
                }

                return $categorized;
            };

            $changes = [];

            // Get changes for each business table (filtered by farm_id)
            $businessTables = [
                'animals' => Animal::class,
                'transactions' => Transaction::class,
                'evenements' => Evenement::class,
                'lots' => Lot::class,
                'notifications' => Notification::class,
                'naissances' => Naissance::class,
            ];

            foreach ($businessTables as $tableName => $modelClass) {
                $records = $modelClass::where('farm_id', $farmId)
                    ->where(function ($query) use ($lastSyncAt, $snapshotTime) {
                        $query->where('updated_at', '>', $lastSyncAt)
                            ->where('updated_at', '<=', $snapshotTime)
                            ->orWhere(function ($q) use ($lastSyncAt, $snapshotTime) {
                                $q->where('deleted_at', '>', $lastSyncAt)
                                  ->where('deleted_at', '<=', $snapshotTime);
                            });
                    })
                    ->withTrashed()
                    ->get();

                $changes[$tableName] = $categorizeChanges($records, $lastSyncAt, $snapshotTime);
            }

            // Reference tables (no farm_id filter, global changes)
            // Send ALL records on first sync, only changed records on subsequent syncs
            $referenceTables = [
                'especes' => Espece::class,
                'categories' => Categorie::class,
                'type_evenements' => TypeEvenement::class,
            ];

            $isFirstSync = $lastSyncAt->year < 2000; // lastSyncAt = 1970-01-01 indicates first sync

            foreach ($referenceTables as $tableName => $modelClass) {
                if ($isFirstSync) {
                    // First sync: send ALL active records (not deleted)
                    $records = $modelClass::whereNull('deleted_at')->get();
                } else {
                    // Subsequent sync: send only changed records
                    $records = $modelClass::where(function ($query) use ($lastSyncAt, $snapshotTime) {
                            $query->where('updated_at', '>', $lastSyncAt)
                                ->where('updated_at', '<=', $snapshotTime)
                                ->orWhere(function ($q) use ($lastSyncAt, $snapshotTime) {
                                    $q->where('deleted_at', '>', $lastSyncAt)
                                      ->where('deleted_at', '<=', $snapshotTime);
                                });
                        })
                        ->withTrashed()
                        ->get();
                }

                // Make timestamps visible for categorization (some models hide them)
                $records->makeVisible(['created_at', 'updated_at', 'deleted_at']);

                $changes[$tableName] = $categorizeChanges($records, $lastSyncAt, $snapshotTime);
            }

            // Farms accessible to the user
            $farmRecords = Farm::where(function ($query) use ($userId) {
                    $query->where('owner_id', $userId)
                        ->orWhereHas('users', function ($q) use ($userId) {
                            $q->where('user_id', $userId);
                        });
                })
                ->where(function ($query) use ($lastSyncAt, $snapshotTime) {
                    $query->where('updated_at', '>', $lastSyncAt)
                        ->where('updated_at', '<=', $snapshotTime)
                        ->orWhere(function ($q) use ($lastSyncAt, $snapshotTime) {
                            $q->where('deleted_at', '>', $lastSyncAt)
                              ->where('deleted_at', '<=', $snapshotTime);
                        });
                })
                ->withTrashed()
                ->with(['owner', 'users'])
                ->get();

            $changes['farms'] = $categorizeChanges($farmRecords, $lastSyncAt, $snapshotTime);

            // Farm user pivot table
            $accessibleFarmIds = Farm::where(function ($query) use ($userId) {
                    $query->where('owner_id', $userId)
                        ->orWhereHas('users', function ($q) use ($userId) {
                            $q->where('user_id', $userId);
                        });
                })
                ->pluck('id')
                ->toArray();

            $farmUserRecords = \DB::table('farm_user')
                ->whereIn('farm_id', $accessibleFarmIds)
                ->where('updated_at', '>', $lastSyncAt)
                ->where('updated_at', '<=', $snapshotTime)
                ->get()
                ->map(function ($record) {
                    // Convertir les timestamps en format ISO 8601 pour cohérence
                    $recordArray = (array) $record;
                    if (isset($recordArray['created_at']) && $recordArray['created_at']) {
                        $recordArray['created_at'] = Carbon::parse($recordArray['created_at'])->toIso8601String();
                    }
                    if (isset($recordArray['updated_at']) && $recordArray['updated_at']) {
                        $recordArray['updated_at'] = Carbon::parse($recordArray['updated_at'])->toIso8601String();
                    }
                    if (isset($recordArray['deleted_at']) && $recordArray['deleted_at']) {
                        $recordArray['deleted_at'] = Carbon::parse($recordArray['deleted_at'])->toIso8601String();
                    }
                    return $recordArray;
                });

            $changes['farm_user'] = $categorizeChanges($farmUserRecords->toArray(), $lastSyncAt, $snapshotTime);

            return ApiResponse::success([
                'changes' => $changes,
                'timestamp' => $snapshotTime->toIso8601String(),
            ], 'Synchronisation pull réussie (WatermelonDB format)');
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), null, 400);
        }
    }

    /**
     * Initial sync for first-time mobile setup
     * Returns all farms accessible to the user + reference tables
     * Does NOT require farm_id (solves chicken-and-egg problem)
     */
    public function initial(Request $request)
    {
        try {
            $userId = $request->user()->id;

            $data = [];

            // Fermes accessibles à l'utilisateur (owner ou membre via farm_user)
            $data['farms'] = Farm::where(function ($query) use ($userId) {
                    $query->where('owner_id', $userId)
                        ->orWhereHas('users', function ($q) use ($userId) {
                            $q->where('user_id', $userId);
                        });
                })
                ->with(['owner', 'users'])
                ->get()
                ->toArray();

            // Table pivot farm_user pour les memberships de l'utilisateur
            $accessibleFarmIds = Farm::where(function ($query) use ($userId) {
                    $query->where('owner_id', $userId)
                        ->orWhereHas('users', function ($q) use ($userId) {
                            $q->where('user_id', $userId);
                        });
                })
                ->pluck('id')
                ->toArray();

            $data['farm_user'] = \DB::table('farm_user')
                ->whereIn('farm_id', $accessibleFarmIds)
                ->get()
                ->toArray();

            // Tables de référence (globales, pas scopées par farm)
            $data['especes'] = Espece::all()->toArray();
            $data['categories'] = Categorie::all()->toArray();
            $data['type_evenements'] = TypeEvenement::all()->toArray();

            return ApiResponse::success([
                'data' => $data,
                'synced_at' => now()->toIso8601String(),
            ], 'Initial sync réussi');
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), null, 400);
        }
    }
}
