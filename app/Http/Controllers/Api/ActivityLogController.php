<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Helpers\ApiResponse;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    private ActivityLogService $activityLogService;

    public function __construct(ActivityLogService $activityLogService)
    {
        $this->activityLogService = $activityLogService;
    }

    /**
     * List activity logs for the current farm with filters
     */
    public function index(Request $request)
    {
        $farmId = $request->header('X-Farm-ID');
        
        if (!$farmId) {
            return ApiResponse::error('X-Farm-ID header is required', 400);
        }

        $filters = [
            'action' => $request->action,
            'model_type' => $request->model_type,
            'user_id' => $request->user_id,
            'date_debut' => $request->date_debut,
            'date_fin' => $request->date_fin,
            'per_page' => $request->per_page,
        ];

        $logs = $this->activityLogService->getForFarm($farmId, $filters);

        return ApiResponse::success([
            'logs' => $logs->map(function ($log) {
                return [
                    'id' => $log->id,
                    'action' => $log->action,
                    'model_type' => $log->model_type,
                    'model_id' => $log->model_id,
                    'description' => $log->description,
                    'old_values' => $log->old_values,
                    'new_values' => $log->new_values,
                    'ip_address' => $log->ip_address,
                    'created_at' => $log->created_at->format('Y-m-d H:i:s'),
                    'user' => $log->user ? [
                        'id' => $log->user->id,
                        'name' => $log->user->name,
                    ] : null,
                    'farm' => $log->farm ? [
                        'id' => $log->farm->id,
                        'name' => $log->farm->name,
                    ] : null,
                ];
            }),
            'meta' => [
                'total' => $logs->total(),
                'per_page' => $logs->perPage(),
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
            ],
        ]);
    }

    /**
     * List activity logs filtered by model type
     */
    public function parModele(Request $request, string $modelType)
    {
        $farmId = $request->header('X-Farm-ID');
        
        if (!$farmId) {
            return ApiResponse::error('X-Farm-ID header is required', 400);
        }

        $filters = [
            'action' => $request->action,
            'model_type' => $modelType,
            'user_id' => $request->user_id,
            'date_debut' => $request->date_debut,
            'date_fin' => $request->date_fin,
            'per_page' => $request->per_page,
        ];

        $logs = $this->activityLogService->getForFarm($farmId, $filters);

        return ApiResponse::success([
            'logs' => $logs->map(function ($log) {
                return [
                    'id' => $log->id,
                    'action' => $log->action,
                    'model_type' => $log->model_type,
                    'model_id' => $log->model_id,
                    'description' => $log->description,
                    'old_values' => $log->old_values,
                    'new_values' => $log->new_values,
                    'ip_address' => $log->ip_address,
                    'created_at' => $log->created_at->format('Y-m-d H:i:s'),
                    'user' => $log->user ? [
                        'id' => $log->user->id,
                        'name' => $log->user->name,
                    ] : null,
                ];
            }),
            'meta' => [
                'total' => $logs->total(),
                'per_page' => $logs->perPage(),
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
            ],
        ]);
    }
}
