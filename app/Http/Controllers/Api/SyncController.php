<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

class SyncController extends Controller
{
    /**
     * Error code constants for sync operations
     */
    private const ERROR_CODE_VERSION_CONFLICT = 'VERSION_CONFLICT';
    private const ERROR_CODE_UUID_INVALID = 'UUID_INVALID';
    private const ERROR_CODE_ID_EXISTS = 'ID_EXISTS';
    private const ERROR_CODE_FK_MISSING = 'FK_MISSING';
    private const ERROR_CODE_PERMISSION_DENIED = 'PERMISSION_DENIED';
    private const ERROR_CODE_VALIDATION_ERROR = 'VALIDATION_ERROR';
    private const ERROR_CODE_SERVER_ERROR = 'SERVER_ERROR';

    /**
     * Permission mapping for sync operations
     * Maps table+action to Spatie permission name
     */
    private array $permissionMap = [
        'animals' => [
            'create' => 'animals.create',
            'update' => 'animals.update',
            'delete' => 'animals.delete',
        ],
        'transactions' => [
            'create' => 'transactions.create',
            'update' => 'transactions.update',
            'delete' => 'transactions.delete',
        ],
        'evenements' => [
            'create' => 'evenements.create',
            'update' => 'evenements.update',
            'delete' => 'evenements.delete',
        ],
        'lots' => [
            'create' => 'lots.create',
            'update' => 'lots.update',
            'delete' => 'lots.delete',
        ],
        'notifications' => [
            'create' => 'notifications.view', // Notifications are typically read-only from sync
            'update' => 'notifications.view',
            'delete' => 'notifications.view',
        ],
        'naissances' => [
            'create' => 'naissances.create',
            'update' => 'naissances.update',
            'delete' => 'naissances.delete',
        ],
        'especes' => [
            'create' => 'especes.create',
            'update' => 'especes.update',
            'delete' => 'especes.delete',
        ],
        'categories' => [
            'create' => 'especes.create', // Categories are part of species management
            'update' => 'especes.update',
            'delete' => 'especes.delete',
        ],
        'type_evenements' => [
            'create' => 'especes.create', // Type events are part of species management
            'update' => 'especes.update',
            'delete' => 'especes.delete',
        ],
        'farms' => [
            'create' => 'farms.create',
            'update' => 'farms.update',
            'delete' => 'farms.delete',
        ],
    ];

    /**
     * Push changes from mobile to server
     */
    public function push(Request $request)
    {
        try {
            Log::info('SYNC/PUSH - Request received', [
                'user_id' => $request->user()?->id,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            $data = $request->validate([
                'changes' => 'required|array',
                'changes.*.table' => 'required|string',
                'changes.*.action' => 'required|in:create,update,delete',
                'changes.*.data' => 'required|array',
                'last_sync_at' => 'required|date',
                'farm_id' => 'required|uuid',
                'sync_request_id' => 'nullable|uuid',
            ]);

            Log::info('SYNC/PUSH - Validation passed', [
                'total_changes' => count($data['changes']),
                'farm_id' => $data['farm_id'],
                'last_sync_at' => $data['last_sync_at'],
            ]);

            $userId = $request->user()->id;
            $farmId = $request->farm_id;
            $syncRequestId = $data['sync_request_id'] ?? null;

            // Check for idempotence: if sync_request_id exists and is completed, return cached results
            if ($syncRequestId) {
                $existingRequest = \DB::table('sync_requests')
                    ->where('id', $syncRequestId)
                    ->where('status', 'completed')
                    ->first();

                if ($existingRequest) {
                    Log::info('SYNC/PUSH - Returning cached results for existing sync_request_id', [
                        'sync_request_id' => $syncRequestId,
                        'user_id' => $userId,
                    ]);
                    $syncedAt = is_string($existingRequest->updated_at) 
                        ? $existingRequest->updated_at 
                        : $existingRequest->updated_at->toIso8601String();
                    return ApiResponse::success([
                        'results' => json_decode($existingRequest->results, true),
                        'conflicts' => [],
                        'synced_at' => $syncedAt,
                        'cached' => true,
                    ], 'Synchronisation push réussie (cached)');
                }

                // Create new sync_request record for tracking
                \DB::table('sync_requests')->insert([
                    'id' => $syncRequestId,
                    'user_id' => $userId,
                    'farm_id' => $farmId,
                    'status' => 'pending',
                    'results' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

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
                Log::warning('SYNC/PUSH - Farm access denied', [
                    'user_id' => $userId,
                    'farm_id' => $farmId,
                ]);
                return ApiResponse::error('Accès non autorisé à cette ferme', null, 403);
            }

            Log::info('SYNC/PUSH - Farm access verified', [
                'farm_id' => $farmId,
                'farm_name' => $farm->name,
            ]);

            $results = [];
            $conflicts = [];

            // Enable SQL query logging for debugging
            DB::enableQueryLog();

            DB::beginTransaction();

            foreach ($data['changes'] as $index => $change) {
                $table = $change['table'];
                $action = $change['action'];
                $recordData = $change['data'];

                Log::debug('SYNC/PUSH - Processing change', [
                    'index' => $index,
                    'table' => $table,
                    'action' => $action,
                    'record_id' => $recordData['id'] ?? null,
                    'data_sample' => array_slice($recordData, 0, 5),
                ]);

                // Create a savepoint for this change so we can rollback individually without aborting the entire transaction
                $savepointName = 'sp_change_' . $index;
                DB::statement("SAVEPOINT {$savepointName}");

                try {
                    // Check Spatie permission for this table+action
                    $permission = $this->permissionMap[$table][$action] ?? null;
                    if ($permission && !$request->user()->can($permission)) {
                        Log::warning('SYNC/PUSH - Permission denied', [
                            'user_id' => $userId,
                            'table' => $table,
                            'action' => $action,
                            'permission' => $permission,
                            'record_id' => $recordData['id'] ?? null,
                        ]);
                        DB::statement("ROLLBACK TO SAVEPOINT {$savepointName}");
                        $results[] = [
                            'table' => $table,
                            'action' => $action,
                            'status' => 'error',
                            'reason' => 'Permission refusée',
                            'code' => self::ERROR_CODE_PERMISSION_DENIED,
                        ];
                        continue;
                    }
                    // Validate farm_id in data matches request farm_id for business tables
                    if (in_array($table, ['animals', 'transactions', 'evenements', 'lots', 'notifications', 'naissances'])) {
                        if (isset($recordData['farm_id']) && $recordData['farm_id'] !== $farmId) {
                            // Verify user has access to the data's farm_id
                            try {
                                $dataFarm = \App\Models\Farm::where('id', $recordData['farm_id'])
                                    ->where(function ($query) use ($userId) {
                                        $query->where('owner_id', $userId)
                                            ->orWhereHas('users', function ($q) use ($userId) {
                                                $q->where('user_id', $userId);
                                            });
                                    })
                                    ->first();
                            } catch (\Exception $e) {
                                Log::error('SYNC/PUSH - Error checking farm access', [
                                    'table' => $table,
                                    'record_id' => $recordData['id'] ?? null,
                                    'data_farm_id' => $recordData['farm_id'],
                                    'error' => $e->getMessage(),
                                ]);
                                DB::statement("ROLLBACK TO SAVEPOINT {$savepointName}");
                                $results[] = [
                                    'table' => $table,
                                    'action' => $action,
                                    'status' => 'error',
                                    'reason' => 'Error verifying farm access',
                                    'code' => self::ERROR_CODE_SERVER_ERROR,
                                ];
                                continue;
                            }

                            if ($dataFarm) {
                                // User has access to both farms, accept the data's farm_id
                                Log::info('SYNC/PUSH - Farm ID mismatch but user has access, accepting data farm_id', [
                                    'table' => $table,
                                    'record_id' => $recordData['id'] ?? null,
                                    'data_farm_id' => $recordData['farm_id'],
                                    'request_farm_id' => $farmId,
                                ]);
                                // Keep the data's farm_id, don't override
                            } else {
                                Log::warning('SYNC/PUSH - Farm ID mismatch and no access to data farm', [
                                    'table' => $table,
                                    'record_id' => $recordData['id'] ?? null,
                                    'data_farm_id' => $recordData['farm_id'],
                                    'request_farm_id' => $farmId,
                                ]);
                                DB::statement("ROLLBACK TO SAVEPOINT {$savepointName}");
                                $conflicts[] = [
                                    'table' => $table,
                                    'id' => $recordData['id'] ?? null,
                                    'reason' => 'Farm-ID mismatch and no access to data farm',
                                ];
                                continue;
                            }
                        }
                        // Ensure farm_id is set if not present
                        if (!isset($recordData['farm_id'])) {
                            $recordData['farm_id'] = $farmId;
                        }
                    }

                    // Validate UUID format for ID fields
                    if (isset($recordData['id']) && !$this->isValidUUID($recordData['id'])) {
                        Log::error('SYNC/PUSH - Invalid UUID format', [
                            'table' => $table,
                            'record_id' => $recordData['id'],
                        ]);
                        DB::statement("ROLLBACK TO SAVEPOINT {$savepointName}");
                        $results[] = [
                            'table' => $table,
                            'action' => $action,
                            'status' => 'error',
                            'reason' => 'Invalid UUID format',
                            'code' => self::ERROR_CODE_UUID_INVALID,
                        ];
                        continue;
                    }

                    // Check for ID conflicts on create
                    if ($action === 'create' && isset($recordData['id'])) {
                        $modelClass = $this->getModelClass($table);
                        if ($modelClass) {
                            try {
                                $exists = $modelClass::withTrashed()->where('id', $recordData['id'])->exists();
                                if ($exists) {
                                    Log::warning('SYNC/PUSH - ID already exists on server', [
                                        'table' => $table,
                                        'record_id' => $recordData['id'],
                                    ]);
                                    $results[] = [
                                        'table' => $table,
                                        'action' => $action,
                                        'status' => 'error',
                                        'reason' => 'ID already exists on server',
                                        'code' => self::ERROR_CODE_ID_EXISTS,
                                    ];
                                    continue;
                                }
                            } catch (\Exception $e) {
                                Log::error('SYNC/PUSH - Error checking ID existence', [
                                    'table' => $table,
                                    'record_id' => $recordData['id'],
                                    'error' => $e->getMessage(),
                                ]);
                                DB::statement("ROLLBACK TO SAVEPOINT {$savepointName}");
                                $results[] = [
                                    'table' => $table,
                                    'action' => $action,
                                    'status' => 'error',
                                    'reason' => 'Error checking ID existence (transaction may be in failed state)',
                                    'code' => self::ERROR_CODE_SERVER_ERROR,
                                ];
                                continue;
                            }
                        }
                    }

                    $result = $this->processChange($table, $action, $recordData, $userId);

                    Log::debug('SYNC/PUSH - Change processed', [
                        'table' => $table,
                        'action' => $action,
                        'result_status' => $result['status'],
                        'result' => $result,
                    ]);

                    if ($result['status'] === 'conflict') {
                        Log::warning('SYNC/PUSH - Conflict detected', [
                            'table' => $table,
                            'id' => $recordData['id'] ?? null,
                            'reason' => $result['reason'],
                        ]);
                        $conflictEntry = [
                            'table' => $table,
                            'id' => $recordData['id'] ?? null,
                            'reason' => $result['reason'],
                        ];
                        // Include code field if present in result
                        if (isset($result['code'])) {
                            $conflictEntry['code'] = $result['code'];
                        }
                        if (isset($result['client_version'])) {
                            $conflictEntry['client_version'] = $result['client_version'];
                        }
                        if (isset($result['server_version'])) {
                            $conflictEntry['server_version'] = $result['server_version'];
                        }
                        $conflicts[] = $conflictEntry;
                    }

                    $results[] = [
                        'table' => $table,
                        'action' => $action,
                        'status' => $result['status'],
                        'reason' => $result['reason'] ?? null,
                        'client_version' => $result['client_version'] ?? null,
                        'server_version' => $result['server_version'] ?? null,
                        'code' => $result['code'] ?? null,
                    ];
                } catch (\Illuminate\Database\QueryException $e) {
                    // Catch FK constraint violations individually - continue processing other items
                    if (str_contains($e->getMessage(), 'foreign key constraint') || str_contains($e->getMessage(), 'violates foreign key')) {
                        Log::error('SYNC/PUSH - Foreign key constraint violation', [
                            'table' => $table,
                            'action' => $action,
                            'record_id' => $recordData['id'] ?? null,
                            'error' => $e->getMessage(),
                            'sql' => $e->getSql(),
                        ]);
                        DB::statement("ROLLBACK TO SAVEPOINT {$savepointName}");
                        $results[] = [
                            'table' => $table,
                            'action' => $action,
                            'status' => 'error',
                            'reason' => 'Référence introuvable (FK) - probablement un animal ou une entité liée pas encore synchronisée',
                            'code' => self::ERROR_CODE_FK_MISSING,
                        ];
                    } else {
                        // Non-FK errors should still trigger global rollback
                        Log::error('SYNC/PUSH - Database query exception (non-FK)', [
                            'table' => $table,
                            'action' => $action,
                            'error' => $e->getMessage(),
                            'sql' => $e->getSql(),
                            'bindings' => $e->getBindings(),
                        ]);
                        DB::statement("ROLLBACK TO SAVEPOINT {$savepointName}");
                        throw $e;
                    }
                } catch (\Exception $e) {
                    Log::error('SYNC/PUSH - Unexpected exception during change processing', [
                        'table' => $table,
                        'action' => $action,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    DB::statement("ROLLBACK TO SAVEPOINT {$savepointName}");
                    throw $e;
                }
            }

            DB::commit();

            // Log SQL queries executed during the sync
            $queries = DB::getQueryLog();
            if (count($queries) > 0) {
                Log::debug('SYNC/PUSH - SQL queries executed', [
                    'total_queries' => count($queries),
                    'queries' => array_slice($queries, 0, 10), // Log first 10 queries to avoid overflow
                ]);
            }

            Log::info('SYNC/PUSH - Transaction committed', [
                'total_processed' => count($results),
                'successful' => count(array_filter($results, fn($r) => in_array($r['status'], ['created', 'updated', 'deleted']))),
                'errors' => count(array_filter($results, fn($r) => $r['status'] === 'error')),
                'conflicts' => count($conflicts),
            ]);

            // Store results in sync_requests if sync_request_id was provided
            if ($syncRequestId) {
                \DB::table('sync_requests')
                    ->where('id', $syncRequestId)
                    ->update([
                        'status' => 'completed',
                        'results' => json_encode($results),
                        'updated_at' => now(),
                    ]);
                Log::info('SYNC/PUSH - Stored results in sync_requests', [
                    'sync_request_id' => $syncRequestId,
                ]);
            }

            return ApiResponse::success([
                'results' => $results,
                'conflicts' => $conflicts,
                'synced_at' => now()->toIso8601String(),
            ], 'Synchronisation push réussie');
        } catch (\Exception $e) {
            // Only rollback for non-FK errors (system errors)
            // FK errors are handled individually per item and should not reach here
            if (!str_contains($e->getMessage(), 'foreign key constraint') && !str_contains($e->getMessage(), 'violates foreign key')) {
                DB::rollBack();
                Log::error('SYNC/PUSH - Transaction rolled back', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
            Log::error('SYNC/PUSH - Request failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return ApiResponse::error($e->getMessage(), null, 400);
        }
    }

    /**
     * Pull changes from server to mobile
     */
    public function pull(Request $request)
    {
        try {
            $request->validate([
                'last_sync_at' => 'required|date',
                'farm_id' => 'required|uuid',
            ]);

            $userId = $request->user()->id;
            $farmId = $request->farm_id;
            $lastSyncAt = Carbon::parse($request->last_sync_at);

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

            $changes = [];

            // Get changes for each table with snapshot consistency
            $changes['animals'] = Animal::where('farm_id', $farmId)
                ->where(function ($query) use ($lastSyncAt, $snapshotTime) {
                    $query->where('updated_at', '>', $lastSyncAt)
                        ->where('updated_at', '<=', $snapshotTime)
                        ->orWhere(function ($q) use ($lastSyncAt, $snapshotTime) {
                            $q->where('deleted_at', '>', $lastSyncAt)
                              ->where('deleted_at', '<=', $snapshotTime);
                        });
                })
                ->withTrashed()
                ->get()
                ->toArray();

            $changes['transactions'] = Transaction::where('farm_id', $farmId)
                ->where(function ($query) use ($lastSyncAt, $snapshotTime) {
                    $query->where('updated_at', '>', $lastSyncAt)
                        ->where('updated_at', '<=', $snapshotTime)
                        ->orWhere(function ($q) use ($lastSyncAt, $snapshotTime) {
                            $q->where('deleted_at', '>', $lastSyncAt)
                              ->where('deleted_at', '<=', $snapshotTime);
                        });
                })
                ->withTrashed()
                ->get()
                ->toArray();

            $changes['evenements'] = Evenement::where('farm_id', $farmId)
                ->where(function ($query) use ($lastSyncAt, $snapshotTime) {
                    $query->where('updated_at', '>', $lastSyncAt)
                        ->where('updated_at', '<=', $snapshotTime)
                        ->orWhere(function ($q) use ($lastSyncAt, $snapshotTime) {
                            $q->where('deleted_at', '>', $lastSyncAt)
                              ->where('deleted_at', '<=', $snapshotTime);
                        });
                })
                ->withTrashed()
                ->get()
                ->toArray();

            $changes['lots'] = Lot::where('farm_id', $farmId)
                ->where(function ($query) use ($lastSyncAt, $snapshotTime) {
                    $query->where('updated_at', '>', $lastSyncAt)
                        ->where('updated_at', '<=', $snapshotTime)
                        ->orWhere(function ($q) use ($lastSyncAt, $snapshotTime) {
                            $q->where('deleted_at', '>', $lastSyncAt)
                              ->where('deleted_at', '<=', $snapshotTime);
                        });
                })
                ->withTrashed()
                ->get()
                ->toArray();

            $changes['notifications'] = Notification::where('farm_id', $farmId)
                ->where(function ($query) use ($lastSyncAt, $snapshotTime) {
                    $query->where('updated_at', '>', $lastSyncAt)
                        ->where('updated_at', '<=', $snapshotTime)
                        ->orWhere(function ($q) use ($lastSyncAt, $snapshotTime) {
                            $q->where('deleted_at', '>', $lastSyncAt)
                              ->where('deleted_at', '<=', $snapshotTime);
                        });
                })
                ->withTrashed()
                ->get()
                ->toArray();

            $changes['naissances'] = Naissance::where('farm_id', $farmId)
                ->where(function ($query) use ($lastSyncAt, $snapshotTime) {
                    $query->where('updated_at', '>', $lastSyncAt)
                        ->where('updated_at', '<=', $snapshotTime)
                        ->orWhere(function ($q) use ($lastSyncAt, $snapshotTime) {
                            $q->where('deleted_at', '>', $lastSyncAt)
                              ->where('deleted_at', '<=', $snapshotTime);
                        });
                })
                ->withTrashed()
                ->get()
                ->toArray();

            // Reference tables (no farm_id, but synced)
            $changes['especes'] = Espece::where(function ($query) use ($lastSyncAt, $snapshotTime) {
                    $query->where('updated_at', '>', $lastSyncAt)
                        ->where('updated_at', '<=', $snapshotTime)
                        ->orWhere(function ($q) use ($lastSyncAt, $snapshotTime) {
                            $q->where('deleted_at', '>', $lastSyncAt)
                              ->where('deleted_at', '<=', $snapshotTime);
                        });
                })
                ->withTrashed()
                ->get()
                ->toArray();

            $changes['categories'] = Categorie::where(function ($query) use ($lastSyncAt, $snapshotTime) {
                    $query->where('updated_at', '>', $lastSyncAt)
                        ->where('updated_at', '<=', $snapshotTime)
                        ->orWhere(function ($q) use ($lastSyncAt, $snapshotTime) {
                            $q->where('deleted_at', '>', $lastSyncAt)
                              ->where('deleted_at', '<=', $snapshotTime);
                        });
                })
                ->withTrashed()
                ->get()
                ->toArray();

            $changes['type_evenements'] = TypeEvenement::where(function ($query) use ($lastSyncAt, $snapshotTime) {
                    $query->where('updated_at', '>', $lastSyncAt)
                        ->where('updated_at', '<=', $snapshotTime)
                        ->orWhere(function ($q) use ($lastSyncAt, $snapshotTime) {
                            $q->where('deleted_at', '>', $lastSyncAt)
                              ->where('deleted_at', '<=', $snapshotTime);
                        });
                })
                ->withTrashed()
                ->get()
                ->toArray();

            // Fermes accessibles à l'utilisateur (owner ou membre via farm_user)
            $changes['farms'] = Farm::where(function ($query) use ($userId) {
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
                ->get()
                ->toArray();

            // Table pivot farm_user pour les memberships
            $accessibleFarmIds = Farm::where(function ($query) use ($userId) {
                    $query->where('owner_id', $userId)
                        ->orWhereHas('users', function ($q) use ($userId) {
                            $q->where('user_id', $userId);
                        });
                })
                ->pluck('id')
                ->toArray();

            $changes['farm_user'] = \DB::table('farm_user')
                ->whereIn('farm_id', $accessibleFarmIds)
                ->where('updated_at', '>', $lastSyncAt)
                ->where('updated_at', '<=', $snapshotTime)
                ->get()
                ->toArray();

            return ApiResponse::success([
                'changes' => $changes,
                'synced_at' => $snapshotTime->toIso8601String(),
            ], 'Synchronisation pull réussie');
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), null, 400);
        }
    }

    /**
     * Process a single change
     */
    private function processChange($table, $action, $data, $userId)
    {
        Log::debug('SYNC/PUSH - processChange called', [
            'table' => $table,
            'action' => $action,
            'data' => $data,
        ]);

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

        if (!isset($modelMap[$table])) {
            Log::error('SYNC/PUSH - Unknown table', ['table' => $table]);
            return ['status' => 'error', 'reason' => 'Table inconnue'];
        }

        $modelClass = $modelMap[$table];
        $isBusinessTable = in_array($table, ['animals', 'transactions', 'evenements', 'lots', 'notifications', 'naissances']);

        // Convertir les noms en UUIDs pour les foreign keys
        $data = $this->normalizeForeignKeys($table, $data);

        // Validate animal_id exists for tables that reference animals
        // NOTE: Disabled for now to allow sync of dependent records when animals are synced separately
        // The client should ensure animals are synced before their dependent records
        /*
        if (isset($data['animal_id']) && $data['animal_id'] !== null) {
            $animalExists = Animal::withTrashed()->where('id', $data['animal_id'])->exists();
            if (!$animalExists) {
                Log::error('SYNC/PUSH - animal_id not found', [
                    'animal_id' => $data['animal_id'],
                    'table' => $table,
                ]);
                return [
                    'status' => 'error',
                    'reason' => "animal_id {$data['animal_id']} introuvable côté serveur",
                ];
            }
        }
        */

        switch ($action) {
            case 'create':
                try {
                    // For animals, check if numero_identification already exists (handle duplicate IDs)
                    if ($table === 'animals' && isset($data['numero_identification']) && isset($data['farm_id'])) {
                        $existingByNumero = Animal::withTrashed()
                            ->where('farm_id', $data['farm_id'])
                            ->where('numero_identification', $data['numero_identification'])
                            ->first();

                        if ($existingByNumero) {
                            Log::info('SYNC/PUSH - Animal with numero_identification already exists, updating instead of create', [
                                'table' => $table,
                                'requested_id' => $data['id'],
                                'existing_id' => $existingByNumero->id,
                                'numero_identification' => $data['numero_identification'],
                            ]);

                            // Update the existing record
                            $data['id'] = $existingByNumero->id; // Use existing ID
                            if ($isBusinessTable) {
                                $data['sync_status'] = 'synced';
                                $data['last_modified_by'] = $userId;
                                $data['version'] = ($existingByNumero->version ?? 1) + 1;
                            }
                            Log::debug('SYNC/PUSH - Updating existing animal (from create)', [
                                'table' => $table,
                                'data' => $data,
                            ]);
                            $existingByNumero->update($data);
                            Log::info('SYNC/PUSH - Animal updated (from create)', [
                                'table' => $table,
                                'id' => $existingByNumero->id,
                                'version' => $existingByNumero->version,
                            ]);
                            return ['status' => 'updated', 'id' => $existingByNumero->id, 'version' => $existingByNumero->version ?? null];
                        }
                    }

                    if ($isBusinessTable) {
                        $data['sync_status'] = 'synced';
                        $data['last_modified_by'] = $userId;
                        $data['version'] = 1;
                    }
                    Log::debug('SYNC/PUSH - Creating record', [
                        'table' => $table,
                        'data' => $data,
                    ]);
                    $record = $modelClass::create($data);
                    Log::info('SYNC/PUSH - Record created', [
                        'table' => $table,
                        'id' => $record->id,
                        'version' => $record->version,
                    ]);
                    return ['status' => 'created', 'id' => $record->id, 'version' => $record->version ?? null];
                } catch (\Exception $e) {
                    Log::error('SYNC/PUSH - Create failed', [
                        'table' => $table,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    throw $e;
                }

            case 'update':
                try {
                    $record = $modelClass::withTrashed()->lockForUpdate()->find($data['id']);
                    if (!$record) {
                        // Record doesn't exist, check if it exists by numero_identification (for animals)
                        if ($table === 'animals' && isset($data['numero_identification']) && isset($data['farm_id'])) {
                            $existingByNumero = Animal::withTrashed()
                                ->where('farm_id', $data['farm_id'])
                                ->where('numero_identification', $data['numero_identification'])
                                ->lockForUpdate()
                                ->first();

                            if ($existingByNumero) {
                                Log::info('SYNC/PUSH - Record found by numero_identification, updating existing', [
                                    'table' => $table,
                                    'requested_id' => $data['id'],
                                    'existing_id' => $existingByNumero->id,
                                    'numero_identification' => $data['numero_identification'],
                                ]);

                                // Update the existing record with the new ID (merge)
                                $data['id'] = $existingByNumero->id; // Use existing ID
                                $record = $existingByNumero;
                            }
                        }

                        if (!$record) {
                            // Still not found, treat as create (offline-first sync pattern)
                            Log::info('SYNC/PUSH - Record not found for update, treating as create', [
                                'table' => $table,
                                'id' => $data['id'],
                            ]);

                            if ($isBusinessTable) {
                                $data['sync_status'] = 'synced';
                                $data['last_modified_by'] = $userId;
                                $data['version'] = 1;
                            }

                            Log::debug('SYNC/PUSH - Creating record (from update)', [
                                'table' => $table,
                                'data' => $data,
                            ]);
                            $record = $modelClass::create($data);
                            Log::info('SYNC/PUSH - Record created (from update)', [
                                'table' => $table,
                                'id' => $record->id,
                                'version' => $record->version,
                            ]);
                            return ['status' => 'created', 'id' => $record->id, 'version' => $record->version ?? null];
                        }
                    }

                    // Strict optimistic locking for business tables
                    if ($isBusinessTable) {
                        $clientVersion = $data['version'] ?? 0;
                        $serverVersion = $record->version ?? 1;

                        // Strict version check: client version must match server version exactly
                        if ($clientVersion !== $serverVersion) {
                            Log::warning('SYNC/PUSH - Version conflict (strict optimistic locking)', [
                                'table' => $table,
                                'id' => $data['id'],
                                'client_version' => $clientVersion,
                                'server_version' => $serverVersion,
                            ]);
                            return [
                                'status' => 'conflict',
                                'reason' => 'Version mismatch (client: ' . $clientVersion . ', server: ' . $serverVersion . ')',
                                'client_version' => $clientVersion,
                                'server_version' => $serverVersion,
                                'code' => self::ERROR_CODE_VERSION_CONFLICT,
                            ];
                        }

                        $data['sync_status'] = 'synced';
                        $data['last_modified_by'] = $userId;
                        $data['version'] = $serverVersion + 1;
                    }

                    Log::debug('SYNC/PUSH - Updating record', [
                        'table' => $table,
                        'id' => $data['id'],
                        'data' => $data,
                    ]);
                    $record->update($data);
                    Log::info('SYNC/PUSH - Record updated', [
                        'table' => $table,
                        'id' => $record->id,
                        'version' => $record->version,
                    ]);
                    return ['status' => 'updated', 'version' => $record->version ?? null];
                } catch (\Exception $e) {
                    Log::error('SYNC/PUSH - Update failed', [
                        'table' => $table,
                        'id' => $data['id'],
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    throw $e;
                }

            case 'delete':
                try {
                    $record = $modelClass::withTrashed()->lockForUpdate()->find($data['id']);
                    if (!$record) {
                        Log::error('SYNC/PUSH - Record not found for delete', [
                            'table' => $table,
                            'id' => $data['id'],
                        ]);
                        return ['status' => 'error', 'reason' => 'Enregistrement non trouvé'];
                    }

                    // Strict optimistic locking for business tables
                    if ($isBusinessTable) {
                        $clientVersion = $data['version'] ?? 0;
                        $serverVersion = $record->version ?? 1;

                        // Strict version check: client version must match server version exactly
                        if ($clientVersion !== $serverVersion) {
                            Log::warning('SYNC/PUSH - Version conflict on delete (strict optimistic locking)', [
                                'table' => $table,
                                'id' => $data['id'],
                                'client_version' => $clientVersion,
                                'server_version' => $serverVersion,
                            ]);
                            return [
                                'status' => 'conflict',
                                'reason' => 'Version mismatch (client: ' . $clientVersion . ', server: ' . $serverVersion . ')',
                                'client_version' => $clientVersion,
                                'server_version' => $serverVersion,
                                'code' => self::ERROR_CODE_VERSION_CONFLICT,
                            ];
                        }

                        $record->sync_status = 'synced';
                        $record->last_modified_by = $userId;
                        $record->version = $serverVersion + 1;
                        $record->save();
                    }

                    Log::debug('SYNC/PUSH - Deleting record', [
                        'table' => $table,
                        'id' => $data['id'],
                    ]);
                    $record->delete();
                    Log::info('SYNC/PUSH - Record deleted', [
                        'table' => $table,
                        'id' => $data['id'],
                    ]);
                    return ['status' => 'deleted'];
                } catch (\Exception $e) {
                    Log::error('SYNC/PUSH - Delete failed', [
                        'table' => $table,
                        'id' => $data['id'],
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    throw $e;
                }

            default:
                Log::error('SYNC/PUSH - Unknown action', ['action' => $action]);
                return ['status' => 'error', 'reason' => 'Action inconnue'];
        }
    }

    /**
     * Normaliser les foreign keys: convertir les noms en UUIDs
     */
    private function normalizeForeignKeys($table, $data)
    {
        // Pour la table evenements: convertir type_evenement_id (nom -> UUID)
        if ($table === 'evenements' && isset($data['type_evenement_id'])) {
            $typeEvenementId = $data['type_evenement_id'];
            // Si ce n'est pas un UUID (format simple check), chercher par nom
            if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $typeEvenementId)) {
                $type = TypeEvenement::where('nom_type', $typeEvenementId)->first();
                if ($type) {
                    $data['type_evenement_id'] = $type->id;
                }
            }
        }

        // Pour la table transactions: convertir categorie_id (nom -> UUID)
        if ($table === 'transactions' && isset($data['categorie_id'])) {
            $categorieId = $data['categorie_id'];
            if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $categorieId)) {
                $categorie = Categorie::where('nom_categorie', $categorieId)->first();
                if ($categorie) {
                    $data['categorie_id'] = $categorie->id;
                }
            }
        }

        // Pour la table animals: convertir espece_id (nom -> UUID)
        if ($table === 'animals' && isset($data['espece_id'])) {
            $especeId = $data['espece_id'];
            if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $especeId)) {
                $espece = Espece::where('nom', $especeId)->first();
                if ($espece) {
                    $data['espece_id'] = $espece->id;
                }
            }
        }

        // Pour la table animals: convertir lot_id (nom -> UUID)
        if ($table === 'animals' && isset($data['lot_id'])) {
            $lotId = $data['lot_id'];
            if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $lotId)) {
                $lot = Lot::where('nom', $lotId)->first();
                if ($lot) {
                    $data['lot_id'] = $lot->id;
                }
            }
        }

        return $data;
    }

    /**
     * Vérifier si une chaîne est un UUID valide
     */
    private function isValidUUID($uuid)
    {
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $uuid);
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

    /**
     * Endpoint de vérification de cohérence pour le mobile
     * Permet au mobile de vérifier quels enregistrements locaux sont désynchronisés
     */
    public function verifyConsistency(Request $request)
    {
        try {
            $request->validate([
                'farm_id' => 'required|uuid',
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
}
