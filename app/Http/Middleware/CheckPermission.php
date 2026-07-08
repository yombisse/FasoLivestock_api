<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Helpers\ApiResponse;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        // Non authentifié
        if (!$user) {
            return ApiResponse::error('Non authentifié.', null, 401);
        }

        // Compte désactivé
        if (!$user->is_active) {
            return ApiResponse::error('Votre compte est désactivé.', null, 403);
        }

        // Superadmin passe toujours (vérification robuste)
        if ($user->roles()->where('name', 'superadmin')->exists()) {
            return $next($request);
        }

        // Vérification de la permission
        if (!$user->hasPermissionTo($permission)) {
            return ApiResponse::error(
                "Vous n'avez pas la permission : {$permission}",
                null,
                403
            );
        }

        // Vérification de l'appartenance farm_user si un farm_id est présent
        $farmId = $request->header('X-Farm-ID') ?? $request->input('farm_id');
        if ($farmId) {
            $farm = \App\Models\Farm::where('id', $farmId)
                ->where(function ($query) use ($user) {
                    $query->where('owner_id', $user->id)
                        ->orWhereHas('users', function ($q) use ($user) {
                            $q->where('user_id', $user->id);
                        });
                })
                ->first();

            if (!$farm) {
                return ApiResponse::error('Accès non autorisé à cette ferme', null, 403);
            }
        }

        return $next($request);
    }
}