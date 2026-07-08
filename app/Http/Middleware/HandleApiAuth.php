<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class HandleApiAuth extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo($request): ?string
    {
        // Pour les requêtes API, retourner null pour une réponse JSON 401
        if ($request->is('api/*')) {
            return null;
        }

        return route('login');
    }

    /**
     * Get the authentication guards for the request.
     */
    protected function authenticate($request, array $guards)
    {
        // Forcer l'utilisation du guard 'api' pour les requêtes API
        if ($request->is('api/*')) {
            $guards = ['api'];
        }
        
        parent::authenticate($request, $guards);
    }
}
