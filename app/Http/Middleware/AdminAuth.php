<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        // ─── 1. Token absent → login ─────────────────────────
        if (!session('admin_token')) {
            return redirect()
                ->route('admin.login')
                ->with('error', 'Veuillez vous connecter pour accéder à cette page.');
        }

        // ─── 2. Session expirée → login ───────────────────────
        $expiresAt = session('admin_token_expires_at');

        if (!$expiresAt || now()->isAfter($expiresAt)) {
            session()->forget(['admin_token', 'admin_user', 'admin_token_expires_at']);

            return redirect()
                ->route('admin.login')
                ->with('error', 'Votre session a expiré. Veuillez vous reconnecter.');
        }

        // ─── 3. Compte désactivé ─────────────────────────────
        $user = session('admin_user');

        if ($user && isset($user['is_active']) && !$user['is_active']) {
            session()->forget(['admin_token', 'admin_user', 'admin_token_expires_at']);

            return redirect()
                ->route('admin.login')
                ->with('error', 'Votre compte a été désactivé. Contactez l\'administrateur.');
        }

        // ─── 4. Renouveler la session à chaque requête ───────
        // Évite l'expiration si l'utilisateur est actif
        session(['admin_token_expires_at' => now()->addHours(8)]);

        return $next($request);
    }
}