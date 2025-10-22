<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'manager' => \App\Http\Middleware\EnsureUserIsManager::class,
            'task.access' => \App\Http\Middleware\EnsureTaskAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Handle API exceptions with consistent JSON responses
        $exceptions->render(function (\Throwable $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                // Simple, consistent API error response
                $statusCode = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;
                $message = $e->getMessage() ?: 'An error occurred';

                // Log the error for debugging
                \Log::error('API Error', [
                    'message' => $message,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'url' => $request->fullUrl(),
                    'user_id' => auth()->id()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'error_code' => class_basename($e),
                    'timestamp' => now()->toISOString()
                ], $statusCode);
            }
        });
    })->create();
