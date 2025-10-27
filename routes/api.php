<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BusinessProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CustomersController;
use App\Http\Controllers\LoansController;

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

// Dashboard and core resources (protected)
Route::middleware(['auth:sanctum', 'throttle:1000,60'])->group(function () {
    Route::get('/dashboard/summary/{businessId}', [DashboardController::class, 'summary']);

    // Customers
    Route::get('/customers', [CustomersController::class, 'index']);
    Route::post('/customers', [CustomersController::class, 'store']);
    Route::get('/customers/{id}', [CustomersController::class, 'show']);
    Route::put('/customers/{id}', [CustomersController::class, 'update']);
    Route::delete('/customers/{id}', [CustomersController::class, 'destroy']);

    // Loans
    Route::get('/loans', [LoansController::class, 'index']);
    Route::post('/loans', [LoansController::class, 'store']);
    Route::get('/loans/{id}', [LoansController::class, 'show']);
    Route::put('/loans/{id}', [LoansController::class, 'update']);
    Route::delete('/loans/{id}', [LoansController::class, 'destroy']);
    Route::post('/loans/{id}/status', [LoansController::class, 'changeStatus']);

    // Loan payments
    Route::post('/loans/{id}/payments', [LoansController::class, 'addPayment']);
});
