<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (Throwable $e, $request) {
            if ($request->is('api/*')) {
                // Extract basic info without exposing sensitive details
                $statusCode = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;

                $response = [
                    'success' => false,
                    'message' => $e->getMessage() ?: 'An error occurred while processing your request.',
                ];

                // Include validation errors if they exist
                if ($e instanceof \Illuminate\Validation\ValidationException) {
                    $response['errors'] = $e->errors();
                    $statusCode = 422;
                }

                // Don't expose internal errors in production
                if (!config('app.debug')) {
                    // Generic messages for common HTTP status codes
                    if ($statusCode === 404) {
                        $response['message'] = 'Resource not found.';
                    } elseif ($statusCode === 403) {
                        $response['message'] = 'Access forbidden.';
                    } elseif ($statusCode === 401) {
                        $response['message'] = 'Unauthorized.';
                    } elseif ($statusCode >= 500) {
                        $response['message'] = 'An internal server error occurred. Please try again later.';
                    }
                }

                return response()->json($response, $statusCode);
            }
        });
    })->create();
