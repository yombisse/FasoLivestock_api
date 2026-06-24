<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use App\Helpers\ApiResponse;

class NotificationController extends Controller
{
    private NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * List paginated notifications for current farm
     */
    public function index(Request $request)
    {
        $farmId = $request->input('current_farm_id');
        $userId = auth()->id();

        if (!$farmId) {
            return ApiResponse::error('Farm-ID manquant', 400);
        }

        $filters = [
            'type' => $request->type,
            'lu' => $request->lu,
            'user_id' => $userId,
            'per_page' => $request->per_page ?? 15,
        ];

        $notifications = $this->notificationService->getForFarm($farmId, $filters);

        return ApiResponse::success([
            'notifications' => $notifications->map(function ($notification) use ($userId) {
                $pivot = $notification->users->where('id', $userId)->first()?->pivot;
                return [
                    'id' => $notification->id,
                    'titre' => $notification->titre,
                    'message' => $notification->message,
                    'type' => $notification->type,
                    'lu' => $pivot ? $pivot->is_read : false,
                    'read_at' => $pivot?->read_at?->format('Y-m-d H:i:s'),
                    'created_at' => $notification->created_at->format('Y-m-d H:i:s'),
                    'sent_at' => $notification->sent_at?->format('Y-m-d H:i:s'),
                    'animal_id' => $notification->animal_id,
                    'animal' => $notification->animal ? [
                        'id' => $notification->animal->id,
                        'nom' => $notification->animal->nom,
                        'numero_identification' => $notification->animal->numero_identification,
                    ] : null,
                    'sante_rappel_id' => $notification->sante_rappel_id,
                    'evenement_id' => $notification->evenement_id,
                ];
            }),
            'meta' => [
                'total' => $notifications->total(),
                'per_page' => $notifications->perPage(),
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
            ],
        ]);
    }

    /**
     * Show notification details
     */
    public function show(Notification $notification)
    {
        $userId = auth()->id();
        $pivot = $notification->users->where('id', $userId)->first()?->pivot;

        return ApiResponse::success([
            'id' => $notification->id,
            'titre' => $notification->titre,
            'message' => $notification->message,
            'type' => $notification->type,
            'lu' => $pivot ? $pivot->is_read : false,
            'read_at' => $pivot?->read_at?->format('Y-m-d H:i:s'),
            'created_at' => $notification->created_at->format('Y-m-d H:i:s'),
            'sent_at' => $notification->sent_at?->format('Y-m-d H:i:s'),
            'farm_id' => $notification->farm_id,
            'animal_id' => $notification->animal_id,
            'animal' => $notification->animal ? [
                'id' => $notification->animal->id,
                'nom' => $notification->animal->nom,
                'numero_identification' => $notification->animal->numero_identification,
            ] : null,
            'sante_rappel_id' => $notification->sante_rappel_id,
            'sante_rappel' => $notification->santeRappel ? [
                'id' => $notification->santeRappel->id,
                'type_rappel' => $notification->santeRappel->type_rappel,
                'date_prevue' => $notification->santeRappel->date_prevue->format('Y-m-d'),
                'statut' => $notification->santeRappel->statut,
            ] : null,
            'evenement_id' => $notification->evenement_id,
        ]);
    }

    /**
     * Mark notification as read for current user
     */
    public function marquerLu(Notification $notification)
    {
        $userId = auth()->id();
        $result = $this->notificationService->marquerLu($notification, $userId);

        if ($result) {
            return ApiResponse::success(['message' => 'Notification marquée comme lue']);
        }

        return ApiResponse::error('Impossible de marquer la notification comme lue', 500);
    }

    /**
     * Mark all unread notifications as read for current user
     */
    public function marquerToutLu(Request $request)
    {
        $farmId = $request->input('current_farm_id');
        $userId = auth()->id();

        if (!$farmId) {
            return ApiResponse::error('Farm-ID manquant', 400);
        }

        $count = $this->notificationService->marquerToutLu($farmId, $userId);

        return ApiResponse::success([
            'message' => "{$count} notifications marquées comme lues",
            'count' => $count,
        ]);
    }

    /**
     * Get unread notifications for current user
     */
    public function nonLues(Request $request)
    {
        $farmId = $request->input('current_farm_id');
        $userId = auth()->id();

        if (!$farmId) {
            return ApiResponse::error('Farm-ID manquant', 400);
        }

        $notifications = $this->notificationService->getNonLues($farmId, $userId);

        return ApiResponse::success([
            'notifications' => $notifications->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'titre' => $notification->titre,
                    'message' => $notification->message,
                    'type' => $notification->type,
                    'created_at' => $notification->created_at->format('Y-m-d H:i:s'),
                    'animal_id' => $notification->animal_id,
                    'animal' => $notification->animal ? [
                        'id' => $notification->animal->id,
                        'nom' => $notification->animal->nom,
                        'numero_identification' => $notification->animal->numero_identification,
                    ] : null,
                ];
            }),
            'count' => $notifications->count(),
        ]);
    }

    /**
     * Soft delete notification
     */
    public function destroy(Notification $notification)
    {
        $result = $notification->delete();

        if ($result) {
            return ApiResponse::success(['message' => 'Notification supprimée']);
        }

        return ApiResponse::error('Impossible de supprimer la notification', 500);
    }

    /**
     * Generate intelligent alerts for the farm
     */
    public function genererAlertes(Request $request)
    {
        $farmId = $request->input('current_farm_id');

        if (!$farmId) {
            return ApiResponse::error('Farm-ID manquant', 400);
        }

        $result = $this->notificationService->genererAlertes($farmId);

        return ApiResponse::success([
            'message' => "{$result['generees']} alertes générées",
            'generees' => $result['generees'],
            'types' => $result['types'],
        ]);
    }
}
