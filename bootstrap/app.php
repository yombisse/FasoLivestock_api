<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Http\Middleware\CheckPermission;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'permission'   => CheckPermission::class,
            'farm.context' => \App\Http\Middleware\FarmContextMiddleware::class,
            'admin.auth'   => \App\Http\Middleware\AdminAuth::class,
            'guest.admin'  => \App\Http\Middleware\GuestAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {

        // ─── Validation errors ────────────────────────────────
        $exceptions->render(function (ValidationException $e, Request $request) {
            // JSON uniquement pour les requêtes API
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur de validation',
                    'errors'  => $e->errors(),
                ], 422);
            }
            // Routes web → Laravel gère nativement (redirect avec erreurs)
            return null;
        });

        // ─── Erreurs HTTP (404, 403...) ───────────────────────
        $exceptions->render(function (HttpException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage() ?: 'Erreur HTTP',
                ], $e->getStatusCode());
            }
            // Routes web → Laravel affiche ses vues d'erreur
            return null;
        });

        // ─── Erreur serveur générique (500) ───────────────────
        $exceptions->render(function (\Throwable $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur serveur',
                ], 500);
            }
            // Routes web → Laravel gère nativement
            return null;
        });

    })
    ->create();