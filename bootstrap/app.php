<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;


return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'farm.context' => \App\Http\Middleware\FarmContextMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {

        //  Validation errors (FormRequest)

        $exceptions->render(function (ValidationException $e, Request $request) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors(),
            ], 422);
        });

        //  Erreurs HTTP (404, 403, etc.)
         
        $exceptions->render(function (HttpException $e, Request $request) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Erreur HTTP',
            ], $e->getStatusCode());
        });

        //Erreur serveur générique (500)

        $exceptions->render(function (Throwable $e, Request $request) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur',
            ], 500);
        });

    })
    ->create();