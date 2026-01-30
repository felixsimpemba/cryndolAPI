<?php

namespace App\Http\Controllers;

abstract class Controller
{
    /**
     * Helper to log error and return sanitised response based on APP_DEBUG.
     */
    protected function logAndResponseError(\Exception $e, string $message = 'Operation failed', int $code = 500): \Illuminate\Http\JsonResponse
    {
        // Log the error (optional, Laravel usually logs unchecked exceptions, but good for explicit handling)
        // \Log::error($message . ': ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => config('app.debug') ? $e->getMessage() : 'An unexpected error occurred. Please try again later.',
        ], $code);
    }
}
