<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class GuestAdmin
{
    public function handle(Request $request, Closure $next)
    {
        // Si déjà connecté → redirect dashboard
        if (session('admin_token')) {
            return redirect()->route('admin.dashboard');
        }

        return $next($request);
    }
}