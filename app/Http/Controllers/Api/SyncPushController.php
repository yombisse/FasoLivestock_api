<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\Animal;
use App\Models\Transaction;
use App\Models\Evenement;
use App\Models\Lot;
use App\Models\Notification;
use App\Models\Naissance;
use App\Models\Espece;
use App\Models\Categorie;
use App\Models\TypeEvenement;
use App\Services\SyncDependencyResolver;
use App\Services\Sync\TransactionDeduplicationService;
use App\Services\Sync\EvenementDeduplicationService;
use App\Services\Sync\NaissanceDeduplicationService;

class SyncPushController extends Controller
{
    protected TransactionDeduplicationService $transactionDedupService;
    protected EvenementDeduplicationService $evenementDedupService;
    protected NaissanceDeduplicationService $naissanceDedupService;

    public function __construct(
        TransactionDeduplicationService $transactionDedupService,
        EvenementDeduplicationService $evenementDedupService,
        NaissanceDeduplicationService $naissanceDedupService
    ) {
        $this->transactionDedupService = $transactionDedupService;
        $this->evenementDedupService = $evenementDedupService;
        $this->naissanceDedupService = $naissanceDedupService;
    }

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
    private const ERROR_CODE_CHUNK_TOO_LARGE = 'CHUNK_TOO_LARGE';

    /**
     * Maximum number of items per chunk to avoid timeouts
     */
    private const MAX_CHUNK_SIZE = 200;

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
            'create' => 'notifications.view',
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
            'create' => 'especes.create',
            'update' => 'especes.update',
            'delete' => 'especes.delete',
        ],
        'type_evenements' => [
            'create' => 'especes.create',
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
     * Push changes from mobile to server (WatermelonDB format)
     * Receives changes grouped by table with created/updated/deleted arrays
     */
    public function push(Request $request)
    {
        try {
            Log::info('SYNC/PUSH - Request received (WatermelonDB format)', [
                'user_id' => $request->user()?->id,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            $data = $request->validate([
                'changes' => 'required|array',
                'last_sync_at' => 'required|date',
                'farm_id' => 'required|string|min:16|max:20',
                'sync_request_id' => 'nullable|string|min:16|max:20',
            ]);

            Log::info('SYNC/PUSH - Validation passed', [
                'total_tables' => count($data['changes']),
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

            // Count total items in chunk
            $totalItems = 0;
            foreach ($data['changes'] as $tableData) {
                if (isset($tableData['created'])) $totalItems += count($tableData['created']);
                if (isset($tableData['updated'])) $totalItems += count($tableData['updated']);
                if (isset($tableData['deleted'])) $totalItems += count($tableData['deleted']);
            }

            // Validate chunk size
            if ($totalItems > self::MAX_CHUNK_SIZE) {
                Log::warning('SYNC/PUSH - Chunk size exceeds maximum', [
                    'total_items' => $totalItems,
                    'max_chunk_size' => self::MAX_CHUNK_SIZE,
                    'farm_id' => $farmId,
                ]);
                return ApiResponse::error(
                    "Chunk trop volumineux: {$totalItems} items (maximum: " . self::MAX_CHUNK_SIZE . ")",
                    null,
                    413
                );
            }

            Log::info('SYNC/PUSH - Chunk size validated', [
                'total_items' => $totalItems,
                'max_chunk_size' => self::MAX_CHUNK_SIZE,
            ]);

            // Resolve dependencies and get processing order
            $dependencyResolver = new SyncDependencyResolver();
            $processingOrder = $dependencyResolver->resolveProcessingOrder($data['changes']);

            // Initialize per-module tracking
            $moduleResults = [
                'evenements' => ['confirmed' => [], 'rejected' => []],
                'transactions' => ['confirmed' => [], 'rejected' => []],
                'naissances' => ['confirmed' => [], 'rejected' => []],
                'notifications' => ['confirmed' => [], 'rejected' => []],
                'animals' => ['confirmed' => [], 'rejected' => []],
                'lots' => ['confirmed' => [], 'rejected' => []],
                'especes' => ['confirmed' => [], 'rejected' => []],
                'categories' => ['confirmed' => [], 'rejected' => []],
                'type_evenements' => ['confirmed' => [], 'rejected' => []],
                'farms' => ['confirmed' => [], 'rejected' => []],
            ];

            // Track rejected items with payload for logging
            $rejectedItems = [];

            // Enable SQL query logging for debugging
            DB::enableQueryLog();

            // Wrap entire chunk processing in a single transaction for FK visibility
            DB::beginTransaction();

            try {
                foreach ($processingOrder as $index => $item) {
                    $table = $item['table'];
                    $action = $item['action'];
                    $recordData = $item['data'];

                    // Normalize WatermelonDB action format to backend format
                    $actionMap = [
                        'created' => 'create',
                        'updated' => 'update',
                        'deleted' => 'delete',
                    ];
                    $action = $actionMap[$action] ?? $action;

                    Log::debug('SYNC/PUSH - Processing item', [
                        'index' => $index,
                        'table' => $table,
                        'action' => $action,
                        'record_id' => $recordData['id'] ?? null,
                    ]);

                    // Create a savepoint for this item to isolate errors
                    $savepointName = 'sp_item_' . $index;
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
                            $reason = 'Permission refusée';
                            $moduleResults[$table]['rejected'][] = [
                                'id' => $recordData['id'] ?? null,
                                'reason' => $reason,
                                'code' => self::ERROR_CODE_PERMISSION_DENIED,
                            ];
                            $rejectedItems[] = [
                                'module' => $table,
                                'record_id' => $recordData['id'] ?? null,
                                'farm_id' => $farmId,
                                'reason' => $reason,
                                'payload_snapshot' => $recordData,
                            ];
                            continue;
                        }

                        // Validate farm_id in data matches request farm_id for business tables
                        if (in_array($table, ['animals', 'transactions', 'evenements', 'lots', 'notifications', 'naissances'])) {
                            if (isset($recordData['farm_id']) && $recordData['farm_id'] !== $farmId) {
                                // Verify user has access to the data's farm_id
                                $dataFarm = \App\Models\Farm::where('id', $recordData['farm_id'])
                                    ->where(function ($query) use ($userId) {
                                        $query->where('owner_id', $userId)
                                            ->orWhereHas('users', function ($q) use ($userId) {
                                                $q->where('user_id', $userId);
                                            });
                                    })
                                    ->first();

                                if (!$dataFarm) {
                                    Log::warning('SYNC/PUSH - Farm ID mismatch and no access to data farm', [
                                        'table' => $table,
                                        'record_id' => $recordData['id'] ?? null,
                                        'data_farm_id' => $recordData['farm_id'],
                                        'request_farm_id' => $farmId,
                                    ]);
                                    DB::statement("ROLLBACK TO SAVEPOINT {$savepointName}");
                                    $reason = 'Farm-ID mismatch and no access to data farm';
                                    $moduleResults[$table]['rejected'][] = [
                                        'id' => $recordData['id'] ?? null,
                                        'reason' => $reason,
                                        'code' => self::ERROR_CODE_PERMISSION_DENIED,
                                    ];
                                    $rejectedItems[] = [
                                        'module' => $table,
                                        'record_id' => $recordData['id'] ?? null,
                                        'farm_id' => $farmId,
                                        'reason' => $reason,
                                        'payload_snapshot' => $recordData,
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
                            $reason = 'Invalid UUID format';
                            $moduleResults[$table]['rejected'][] = [
                                'id' => $recordData['id'] ?? null,
                                'reason' => $reason,
                                'code' => self::ERROR_CODE_UUID_INVALID,
                            ];
                            $rejectedItems[] = [
                                'module' => $table,
                                'record_id' => $recordData['id'] ?? null,
                                'farm_id' => $farmId,
                                'reason' => $reason,
                                'payload_snapshot' => $recordData,
                            ];
                            continue;
                        }

                        // Anti-duplication de transaction (règle du contrat d'interface)
                        // Si une transaction liée au même evenement_id existe déjà (créée par EvenementTransactionService via l'observer),
                        // on ignore silencieusement l'item côté client pour éviter la duplication.
                        if ($table === 'transactions' && $action === 'create' && isset($recordData['evenement_id'])) {
                            $existingTransaction = Transaction::where('evenement_id', $recordData['evenement_id'])
                                ->where('farm_id', $recordData['farm_id'] ?? $farmId)
                                ->first();

                            if ($existingTransaction) {
                                Log::info('SYNC/PUSH - Transaction already exists for evenement, ignoring client transaction', [
                                    'evenement_id' => $recordData['evenement_id'],
                                    'existing_transaction_id' => $existingTransaction->id,
                                    'client_transaction_id' => $recordData['id'],
                                ]);
                                DB::statement("ROLLBACK TO SAVEPOINT {$savepointName}");
                                $reason = 'Transaction already exists for this evenement (created by server)';
                                $moduleResults[$table]['rejected'][] = [
                                    'id' => $recordData['id'] ?? null,
                                    'reason' => $reason,
                                    'existing_id' => $existingTransaction->id,
                                ];
                                $rejectedItems[] = [
                                    'module' => $table,
                                    'record_id' => $recordData['id'] ?? null,
                                    'farm_id' => $farmId,
                                    'reason' => $reason,
                                    'payload_snapshot' => $recordData,
                                ];
                                continue;
                            }
                        }

                        // Normalize foreign keys (convert names to UUIDs)
                        $recordData = $this->normalizeForeignKeys($table, $recordData);

                        // Remove WatermelonDB internal fields
                        $recordData = $this->removeWatermelonDBFields($recordData);

                        // Convert timestamps from milliseconds to datetime format
                        $recordData = $this->convertTimestamps($recordData);

                        Log::debug('SYNC/PUSH - Data after normalization', [
                            'table' => $table,
                            'record_id' => $recordData['id'] ?? null,
                            'data_keys' => array_keys($recordData),
                        ]);

                        // Process the change with idempotence (updateOrCreate)
                        $result = $this->processChangeWithIdempotence($table, $action, $recordData, $userId);

                        // Structured logging per item for production debugging
                        Log::info('SYNC/PUSH - Item processed', [
                            'farm_id' => $farmId,
                            'table' => $table,
                            'action' => $action,
                            'record_id' => $recordData['id'] ?? null,
                            'status' => $result['status'],
                            'reason' => $result['reason'] ?? null,
                            'code' => $result['code'] ?? null,
                            'index' => $index,
                        ]);

                        // Track successful items for verification after commit
                        if (in_array($result['status'], ['created', 'updated', 'deleted', 'merged'])) {
                            $moduleResults[$table]['confirmed'][] = $recordData['id'] ?? $result['id'];
                        } elseif ($result['status'] === 'error' || $result['status'] === 'conflict') {
                            $reason = $result['reason'] ?? 'Unknown error';
                            $moduleResults[$table]['rejected'][] = [
                                'id' => $recordData['id'] ?? null,
                                'reason' => $reason,
                                'code' => $result['code'] ?? self::ERROR_CODE_VALIDATION_ERROR,
                            ];
                            $rejectedItems[] = [
                                'module' => $table,
                                'record_id' => $recordData['id'] ?? null,
                                'farm_id' => $farmId,
                                'reason' => $reason,
                                'payload_snapshot' => $recordData,
                            ];
                        }
                    } catch (\Illuminate\Database\QueryException $e) {
                        // Catch FK constraint violations individually - continue processing other items
                        if (str_contains($e->getMessage(), 'foreign key constraint') || str_contains($e->getMessage(), 'violates foreign key')) {
                            Log::error('SYNC/PUSH - Foreign key constraint violation', [
                                'table' => $table,
                                'action' => $action,
                                'record_id' => $recordData['id'] ?? null,
                                'error' => $e->getMessage(),
                            ]);
                            DB::statement("ROLLBACK TO SAVEPOINT {$savepointName}");
                            $reason = 'Foreign key reference not found (FK_MISSING)';
                            $moduleResults[$table]['rejected'][] = [
                                'id' => $recordData['id'] ?? null,
                                'reason' => $reason,
                                'code' => self::ERROR_CODE_FK_MISSING,
                            ];
                            $rejectedItems[] = [
                                'module' => $table,
                                'record_id' => $recordData['id'] ?? null,
                                'farm_id' => $farmId,
                                'reason' => $reason,
                                'payload_snapshot' => $recordData,
                            ];
                        } else {
                            // Non-FK errors should still trigger global rollback
                            Log::error('SYNC/PUSH - Database query exception (non-FK)', [
                                'table' => $table,
                                'action' => $action,
                                'error' => $e->getMessage(),
                            ]);
                            DB::statement("ROLLBACK TO SAVEPOINT {$savepointName}");
                            throw $e;
                        }
                    } catch (\Exception $e) {
                        Log::error('SYNC/PUSH - Unexpected exception during item processing', [
                            'table' => $table,
                            'action' => $action,
                            'error' => $e->getMessage(),
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
                        'queries' => array_slice($queries, 0, 10),
                    ]);
                }

                Log::info('SYNC/PUSH - Transaction committed', [
                    'total_processed' => count($processingOrder),
                ]);

                // VERIFY: After commit, SELECT to confirm each record actually exists in database
                foreach ($moduleResults as $table => $results) {
                    if (empty($results['confirmed'])) {
                        continue;
                    }

                    $modelClass = $this->getModelClass($table);
                    if (!$modelClass) {
                        continue;
                    }

                    $verifiedIds = [];
                    foreach ($results['confirmed'] as $id) {
                        if (!$id) {
                            continue;
                        }
                        $exists = $modelClass::withTrashed()->where('id', $id)->exists();
                        if ($exists) {
                            $verifiedIds[] = $id;
                        } else {
                            // Record was not actually persisted - move to rejected
                            Log::warning('SYNC/PUSH - Verification failed: record not found after commit', [
                                'table' => $table,
                                'id' => $id,
                            ]);
                            $moduleResults[$table]['rejected'][] = [
                                'id' => $id,
                                'reason' => 'Record not found in database after commit (verification failed)',
                                'code' => self::ERROR_CODE_SERVER_ERROR,
                            ];
                            $rejectedItems[] = [
                                'module' => $table,
                                'record_id' => $id,
                                'farm_id' => $farmId,
                                'reason' => 'Record not found in database after commit (verification failed)',
                                'payload_snapshot' => null,
                            ];
                        }
                    }
                    $moduleResults[$table]['confirmed'] = $verifiedIds;
                }

                // Log rejected items to sync_error_logs table
                if (!empty($rejectedItems)) {
                    foreach ($rejectedItems as $rejected) {
                        \DB::table('sync_error_logs')->insert([
                            'id' => substr(Str::random(20), 0, 20),
                            'module' => $rejected['module'],
                            'record_id' => $rejected['record_id'],
                            'farm_id' => $rejected['farm_id'],
                            'reason' => $rejected['reason'],
                            'payload_snapshot' => json_encode($rejected['payload_snapshot']),
                            'occurred_at' => now(),
                        ]);
                    }
                    Log::info('SYNC/PUSH - Logged rejected items to sync_error_logs', [
                        'count' => count($rejectedItems),
                    ]);
                }

                // Store results in sync_requests if sync_request_id was provided
                if ($syncRequestId) {
                    \DB::table('sync_requests')
                        ->where('id', $syncRequestId)
                        ->update([
                            'status' => 'completed',
                            'results' => json_encode($moduleResults),
                            'updated_at' => now(),
                        ]);
                    Log::info('SYNC/PUSH - Stored results in sync_requests', [
                        'sync_request_id' => $syncRequestId,
                    ]);
                }

                return ApiResponse::success([
                    'results' => $moduleResults,
                    'synced_at' => now()->toIso8601String(),
                ], 'Synchronisation push réussie (WatermelonDB format)');
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('SYNC/PUSH - Transaction rolled back', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error('SYNC/PUSH - Request failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return ApiResponse::error($e->getMessage(), null, 400);
        }
    }

    /**
     * Process a single change with idempotence (updateOrCreate)
     * Ensures retries don't create duplicates
     */
    private function processChangeWithIdempotence($table, $action, $data, $userId)
    {
        Log::debug('SYNC/PUSH - processChangeWithIdempotence called', [
            'table' => $table,
            'action' => $action,
            'data' => $data,
        ]);

        // Normaliser les statuts mobiles vers les statuts backend
        $data = $this->normalizeMobileStatuts($table, $data);

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
            'farms' => \App\Models\Farm::class,
        ];

        if (!isset($modelMap[$table])) {
            Log::error('SYNC/PUSH - Unknown table', ['table' => $table]);
            return ['status' => 'error', 'reason' => 'Table inconnue'];
        }

        $modelClass = $modelMap[$table];
        $isBusinessTable = in_array($table, ['animals', 'transactions', 'evenements', 'lots', 'notifications', 'naissances']);

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

                    // Déduplication pour les transactions
                    if ($table === 'transactions' && $this->transactionDedupService->shouldMerge($data)) {
                        $existing = $this->transactionDedupService->findDuplicate($data, $data['id'] ?? null);
                        if ($existing && $existing->id !== ($data['id'] ?? null)) {
                            // Doublon détecté : fusionner avec priorité backend
                            $merged = $this->transactionDedupService->merge($existing, $data);
                            Log::info('SYNC/PUSH - Transaction merged (deduplication)', [
                                'table' => $table,
                                'mobile_id' => $data['id'],
                                'backend_id' => $existing->id,
                                'business_key' => $this->transactionDedupService->generateBusinessKey($data),
                            ]);
                            return [
                                'status' => 'merged',
                                'id' => $merged->id,
                            ];
                        }
                    }

                    // Déduplication pour les événements critiques (saillie, gestation, décès, mise bas)
                    if ($table === 'evenements' && $this->evenementDedupService->shouldDeduplicate($data)) {
                        $existing = $this->evenementDedupService->findDuplicate($data, $data['id'] ?? null);
                        if ($existing && $existing->id !== ($data['id'] ?? null)) {
                            // Doublon détecté : fusionner avec priorité backend
                            $merged = $this->evenementDedupService->merge($existing, $data);
                            Log::info('SYNC/PUSH - Evenement merged (deduplication)', [
                                'table' => $table,
                                'mobile_id' => $data['id'],
                                'backend_id' => $existing->id,
                                'business_key' => $this->evenementDedupService->generateBusinessKey($data),
                            ]);
                            return [
                                'status' => 'merged',
                                'id' => $merged->id,
                            ];
                        }
                    }

                    // Déduplication pour les naissances
                    if ($table === 'naissances') {
                        $existing = $this->naissanceDedupService->findDuplicate($data, $data['id'] ?? null);
                        if ($existing && $existing->id !== ($data['id'] ?? null)) {
                            // Doublon détecté : fusionner avec priorité backend
                            $merged = $this->naissanceDedupService->merge($existing, $data);
                            Log::info('SYNC/PUSH - Naissance merged (deduplication)', [
                                'table' => $table,
                                'mobile_id' => $data['id'],
                                'backend_id' => $existing->id,
                                'business_key' => $this->naissanceDedupService->generateBusinessKey($data),
                            ]);
                            return [
                                'status' => 'merged',
                                'id' => $merged->id,
                            ];
                        }
                    }

                    // Pour les événements de reproduction, utiliser le service avec skipValidation pour le mode offline-first
                    if ($table === 'evenements' && isset($data['type_evenement_id'])) {
                        $typeEvenement = TypeEvenement::find($data['type_evenement_id']);
                        if ($typeEvenement && in_array($typeEvenement->categorie, ['REPRODUCTION', 'Reproduction'])) {
                            // Utiliser EvenementReproductionService avec skipValidation = true
                            // Le mobile a déjà validé la chaîne reproductif localement
                            $reproductionService = app(\App\Services\EvenementReproductionService::class);
                            $record = $reproductionService->store($data, auth()->id() ?? 'system', true);

                            Log::info('SYNC/PUSH - Reproduction event created with skipValidation', [
                                'table' => $table,
                                'id' => $record->id,
                                'type' => $typeEvenement->nom_type,
                            ]);

                            return [
                                'status' => 'created',
                                'id' => $record->id,
                            ];
                        }
                    }

                    // Use updateOrCreate for idempotence
                    $record = $modelClass::updateOrCreate(
                        ['id' => $data['id']],
                        $data
                    );

                    Log::info('SYNC/PUSH - Record created/updated (idempotent)', [
                        'table' => $table,
                        'id' => $record->id,
                        'was_created' => $record->wasRecentlyCreated,
                    ]);

                    return [
                        'status' => $record->wasRecentlyCreated ? 'created' : 'updated',
                        'id' => $record->id,
                    ];
                } catch (\Exception $e) {
                    Log::error('SYNC/PUSH - Create/Update failed', [
                        'table' => $table,
                        'error' => $e->getMessage(),
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

            case 'deleted':
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
                $lot = Lot::where('nom_lot', $lotId)->first();
                if ($lot) {
                    $data['lot_id'] = $lot->id;
                }
            }
        }

        return $data;
    }

    /**
     * Vérifier si une chaîne est un ID valide (string simple)
     * Accepte les IDs simples WatermelonDB (16-20 caractères alphanumériques)
     */
    private function isValidUUID($uuid)
    {
        // Accept simple string IDs (16-20 characters, alphanumeric)
        return preg_match('/^[a-zA-Z0-9]{16,20}$/', $uuid);
    }

    /**
     * Remove WatermelonDB internal fields from record data
     */
    private function removeWatermelonDBFields($data)
    {
        $watermelonFields = ['_status', '_changed', '_created_at', '_updated_at'];
        foreach ($watermelonFields as $field) {
            unset($data[$field]);
        }
        return $data;
    }

    /**
     * Convert timestamps from milliseconds to datetime format
     * WatermelonDB sends created_at/updated_at as milliseconds
     */
    private function convertTimestamps($data)
    {
        $timestampFields = ['created_at', 'updated_at', 'deleted_at'];
        foreach ($timestampFields as $field) {
            if (isset($data[$field]) && is_numeric($data[$field])) {
                // Convert milliseconds to seconds for Carbon
                $data[$field] = \Carbon\Carbon::createFromTimestampMs($data[$field])->toDateTimeString();
            }
        }
        return $data;
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
            'farms' => \App\Models\Farm::class,
        ];

        return $modelMap[$table] ?? null;
    }

    /**
     * Normaliser les statuts mobiles vers les statuts backend
     * Convertit ACTIF/INACTIF (mobile) vers SAIN (backend)
     */
    private function normalizeMobileStatuts(string $table, array $data): array
    {
        // Conversion pour la table animals
        if ($table === 'animals' && isset($data['statut'])) {
            if ($data['statut'] === 'ACTIF' || $data['statut'] === 'INACTIF') {
                $data['statut'] = 'SAIN';
                Log::debug('SYNC/PUSH - Normalized animal statut', [
                    'old_statut' => 'ACTIF/INACTIF',
                    'new_statut' => 'SAIN',
                ]);
            }
        }

        // Conversion pour la table evenements (statut_avant et statut_apres)
        if ($table === 'evenements') {
            if (isset($data['statut_avant']) && ($data['statut_avant'] === 'ACTIF' || $data['statut_avant'] === 'INACTIF')) {
                $data['statut_avant'] = 'SAIN';
                Log::debug('SYNC/PUSH - Normalized evenement statut_avant', [
                    'old_statut' => 'ACTIF/INACTIF',
                    'new_statut' => 'SAIN',
                ]);
            }
            if (isset($data['statut_apres']) && ($data['statut_apres'] === 'ACTIF' || $data['statut_apres'] === 'INACTIF')) {
                $data['statut_apres'] = 'SAIN';
                Log::debug('SYNC/PUSH - Normalized evenement statut_apres', [
                    'old_statut' => 'ACTIF/INACTIF',
                    'new_statut' => 'SAIN',
                ]);
            }
        }

        return $data;
    }
}
