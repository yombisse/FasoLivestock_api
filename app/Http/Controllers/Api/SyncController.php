<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Animal;
use App\Models\Transaction;
use App\Models\Evenement;
use App\Models\Lot;
use App\Models\Notification;
use App\Models\Naissance;
use App\Models\Espece;
use App\Models\Categorie;
use App\Models\TypeEvenement;
use Carbon\Carbon;

class SyncController extends Controller
{
    /**
     * Push changes from mobile to server
     */
    public function push(Request $request)
    {
        try {
            $data = $request->validate([
                'changes' => 'required|array',
                'changes.*.table' => 'required|string',
                'changes.*.action' => 'required|in:create,update,delete',
                'changes.*.data' => 'required|array',
                'last_sync_at' => 'required|date',
                'farm_id' => 'required|uuid',
            ]);

            $userId = $request->user()->id;
            $farmId = $request->farm_id;

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

            $results = [];
            $conflicts = [];

            DB::beginTransaction();

            foreach ($data['changes'] as $change) {
                $table = $change['table'];
                $action = $change['action'];
                $recordData = $change['data'];

                // Validate farm_id in data matches request farm_id for business tables
                if (in_array($table, ['animals', 'transactions', 'evenements', 'lots', 'notifications', 'naissances'])) {
                    if (isset($recordData['farm_id']) && $recordData['farm_id'] !== $farmId) {
                        $conflicts[] = [
                            'table' => $table,
                            'id' => $recordData['id'] ?? null,
                            'reason' => 'Farm-ID mismatch',
                        ];
                        continue;
                    }
                    // Ensure farm_id is set
                    $recordData['farm_id'] = $farmId;
                }

                $result = $this->processChange($table, $action, $recordData, $userId);

                if ($result['status'] === 'conflict') {
                    $conflicts[] = [
                        'table' => $table,
                        'id' => $recordData['id'] ?? null,
                        'reason' => $result['reason'],
                    ];
                }

                $results[] = [
                    'table' => $table,
                    'action' => $action,
                    'status' => $result['status'],
                ];
            }

            DB::commit();

            return ApiResponse::success([
                'results' => $results,
                'conflicts' => $conflicts,
                'synced_at' => now()->toIso8601String(),
            ], 'Synchronisation push réussie');
        } catch (\Exception $e) {
            DB::rollBack();
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

            // Get changes for each table
            $changes['animals'] = Animal::where('farm_id', $farmId)
                ->where(function ($query) use ($lastSyncAt) {
                    $query->where('updated_at', '>', $lastSyncAt)
                        ->orWhere('deleted_at', '>', $lastSyncAt);
                })
                ->withTrashed()
                ->get()
                ->toArray();

            $changes['transactions'] = Transaction::where('farm_id', $farmId)
                ->where(function ($query) use ($lastSyncAt) {
                    $query->where('updated_at', '>', $lastSyncAt)
                        ->orWhere('deleted_at', '>', $lastSyncAt);
                })
                ->withTrashed()
                ->get()
                ->toArray();

            $changes['evenements'] = Evenement::where('farm_id', $farmId)
                ->where(function ($query) use ($lastSyncAt) {
                    $query->where('updated_at', '>', $lastSyncAt)
                        ->orWhere('deleted_at', '>', $lastSyncAt);
                })
                ->withTrashed()
                ->get()
                ->toArray();

            $changes['lots'] = Lot::where('farm_id', $farmId)
                ->where(function ($query) use ($lastSyncAt) {
                    $query->where('updated_at', '>', $lastSyncAt)
                        ->orWhere('deleted_at', '>', $lastSyncAt);
                })
                ->withTrashed()
                ->get()
                ->toArray();

            $changes['notifications'] = Notification::where('farm_id', $farmId)
                ->where(function ($query) use ($lastSyncAt) {
                    $query->where('updated_at', '>', $lastSyncAt)
                        ->orWhere('deleted_at', '>', $lastSyncAt);
                })
                ->withTrashed()
                ->get()
                ->toArray();

            $changes['naissances'] = Naissance::where('farm_id', $farmId)
                ->where(function ($query) use ($lastSyncAt) {
                    $query->where('updated_at', '>', $lastSyncAt)
                        ->orWhere('deleted_at', '>', $lastSyncAt);
                })
                ->withTrashed()
                ->get()
                ->toArray();

            // Reference tables (no farm_id, but synced)
            $changes['especes'] = Espece::where(function ($query) use ($lastSyncAt) {
                    $query->where('updated_at', '>', $lastSyncAt)
                        ->orWhere('deleted_at', '>', $lastSyncAt);
                })
                ->withTrashed()
                ->get()
                ->toArray();

            $changes['categories'] = Categorie::where(function ($query) use ($lastSyncAt) {
                    $query->where('updated_at', '>', $lastSyncAt)
                        ->orWhere('deleted_at', '>', $lastSyncAt);
                })
                ->withTrashed()
                ->get()
                ->toArray();

            $changes['type_evenements'] = TypeEvenement::where(function ($query) use ($lastSyncAt) {
                    $query->where('updated_at', '>', $lastSyncAt)
                        ->orWhere('deleted_at', '>', $lastSyncAt);
                })
                ->withTrashed()
                ->get()
                ->toArray();

            return ApiResponse::success([
                'changes' => $changes,
                'synced_at' => now()->toIso8601String(),
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
        ];

        if (!isset($modelMap[$table])) {
            return ['status' => 'error', 'reason' => 'Table inconnue'];
        }

        $modelClass = $modelMap[$table];
        $isBusinessTable = in_array($table, ['animals', 'transactions', 'evenements', 'lots', 'notifications', 'naissances']);

        switch ($action) {
            case 'create':
                if ($isBusinessTable) {
                    $data['sync_status'] = 'synced';
                    $data['last_modified_by'] = $userId;
                    $data['version'] = 1;
                }
                $record = $modelClass::create($data);
                return ['status' => 'created', 'id' => $record->id, 'version' => $record->version ?? null];

            case 'update':
                $record = $modelClass::withTrashed()->find($data['id']);
                if (!$record) {
                    return ['status' => 'error', 'reason' => 'Enregistrement non trouvé'];
                }

                // Version-based conflict detection for business tables
                if ($isBusinessTable) {
                    $clientVersion = $data['version'] ?? 0;
                    $serverVersion = $record->version ?? 1;

                    if ($clientVersion !== $serverVersion) {
                        // Mark as conflict
                        $record->sync_status = 'conflict';
                        $record->save();
                        return ['status' => 'conflict', 'reason' => 'Version mismatch (client: ' . $clientVersion . ', server: ' . $serverVersion . ')'];
                    }

                    $data['sync_status'] = 'synced';
                    $data['last_modified_by'] = $userId;
                    $data['version'] = $serverVersion + 1;
                }

                $record->update($data);
                return ['status' => 'updated', 'version' => $record->version ?? null];

            case 'delete':
                $record = $modelClass::withTrashed()->find($data['id']);
                if (!$record) {
                    return ['status' => 'error', 'reason' => 'Enregistrement non trouvé'];
                }

                // Version-based conflict detection for soft deletes
                if ($isBusinessTable) {
                    $clientVersion = $data['version'] ?? 0;
                    $serverVersion = $record->version ?? 1;

                    if ($clientVersion !== $serverVersion) {
                        $record->sync_status = 'conflict';
                        $record->save();
                        return ['status' => 'conflict', 'reason' => 'Version mismatch (client: ' . $clientVersion . ', server: ' . $serverVersion . ')'];
                    }

                    $record->sync_status = 'synced';
                    $record->last_modified_by = $userId;
                    $record->version = $serverVersion + 1;
                    $record->save();
                }

                $record->delete();
                return ['status' => 'deleted'];

            default:
                return ['status' => 'error', 'reason' => 'Action inconnue'];
        }
    }
}
