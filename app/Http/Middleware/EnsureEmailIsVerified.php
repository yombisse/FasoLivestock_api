<?php

namespace App\Http\Middleware;

use App\Helpers\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmailIsVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && is_null($request->user()->email_verified_at)) {
            return response()->json(ApiResponse::error('Adresse email non vérifiée. Veuillez vérifier votre email avant de continuer.', 403), 403);
        }

        return $next($request);
    }
}
