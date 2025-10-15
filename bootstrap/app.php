<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            // Temporarily disabled SecurityHeaders - causing blank page
            // \App\Http\Middleware\SecurityHeaders::class,
        ]);

        // Register role middleware alias
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Handle CSRF token mismatch errors
        $exceptions->render(function (\Illuminate\Session\TokenMismatchException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'CSRF token mismatch. Молимо освежите страницу и покушајте поново.',
                    'error' => 'csrf_token_mismatch',
                ], 419);
            }
        });

        // Handle general exceptions for JSON requests
        $exceptions->render(function (\Throwable $e, $request) {
            if ($request->expectsJson() && !($e instanceof \Illuminate\Validation\ValidationException)) {
                \Log::error('Exception in JSON request', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'url' => $request->fullUrl(),
                ]);

                return response()->json([
                    'message' => $e->getMessage(),
                    'error' => get_class($e),
                ], $e->getCode() ?: 500);
            }
        });
    })->create();
