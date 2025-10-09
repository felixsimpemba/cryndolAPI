<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BusinessProfileController;
use App\Http\Controllers\DashboardController;

// Public authentication routes with rate limiting
Route::prefix('auth')->group(function () {
    // Registration (5 attempts per hour per IP)
    Route::post('/register/personal', [AuthController::class, 'registerPersonal'])
        ->middleware('throttle:5,60');

    // Login (10 attempts per hour per IP)
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:10,60');

    // Refresh token (no specific rate limit, but still throttled)
    Route::post('/refresh', [AuthController::class, 'refresh'])
        ->middleware('throttle:30,60');
});

// Protected routes (require authentication with rate limiting)
Route::middleware(['auth:sanctum', 'throttle:1000,60'])->prefix('auth')->group(function () {
    // Authentication management
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);

    // Business profile management
    Route::post('/business-profile', [BusinessProfileController::class, 'create']);
    Route::put('/business-profile', [BusinessProfileController::class, 'update']);
    Route::delete('/business-profile', [BusinessProfileController::class, 'destroy']);
});

// Dashboard routes (protected)
Route::middleware(['auth:sanctum', 'throttle:1000,60'])->group(function () {
    Route::get('/dashboard/summary', [DashboardController::class, 'summary']);
});
