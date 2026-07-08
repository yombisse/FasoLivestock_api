<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Farm;

class FarmContextMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        $farmId = $request->header('X-Farm-ID') ?? $request->input('farm_id');

        if (!$user) {
            return response()->json(['error' => 'Non authentifié'], 401);
        }

        // Superadmin peut voir toutes les données sans farm_id
        if ($user->hasRole('superadmin')) {
            if ($farmId) {
                // Si un farm_id est fourni, vérifier l'accès
                $farm = Farm::where('id', $farmId)->first();
                if (!$farm) {
                    return response()->json(['error' => 'Ferme introuvable'], 404);
                }
                $request->merge(['current_farm_id' => $farmId]);
            }
            return $next($request);
        }

        if (!$farmId) {
            return response()->json(['error' => 'Farm-ID manquant'], 400);
        }

        // Verify user has access to this farm
        $farm = Farm::where('id', $farmId)
            ->where(function ($query) use ($user) {
                $query->where('owner_id', $user->id)
                    ->orWhereHas('users', function ($q) use ($user) {
                        $q->where('user_id', $user->id);
                    });
            })
            ->first();

        if (!$farm) {
            return response()->json(['error' => 'Accès non autorisé à cette ferme'], 403);
        }

        // Store farm context in request for later use
        $request->merge(['current_farm_id' => $farmId]);

        return $next($request);
    }
}
