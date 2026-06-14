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

        // Superadmin passe toujours
        if ($user->hasRole('superadmin')) {
            return $next($request);
        }

        // Vérification de la permission
        if (!$user->hasPermissionTo($permission, 'api')) {
            return ApiResponse::error(
                "Vous n'avez pas la permission : {$permission}",
                null,
                403
            );
        }

        return $next($request);
    }
}