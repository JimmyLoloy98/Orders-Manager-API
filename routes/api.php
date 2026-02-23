<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\HealthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Health check (no auth required)
    Route::get('/health', [HealthController::class, 'check']);

    // Authentication routes (no auth required)
    Route::post('/auth/login', [AuthController::class, 'login']);

    // Protected routes (require authentication)
    Route::middleware('auth:sanctum')->group(function () {
        // Auth
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        // Dashboard
        Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
        Route::get('/dashboard/recent-activity', [DashboardController::class, 'recentActivity']);
        Route::get('/dashboard/monthly-overview', [DashboardController::class, 'monthlyOverview']);
        Route::get('/dashboard/scrap-collection', [DashboardController::class, 'scrapCollection']);

        // Clients
        Route::get('/clients', [ClientController::class, 'index']);
    });
});
