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
        Route::get('/dashboard/daily-overview', [DashboardController::class, 'dailyOverview']);


        // Tables
        Route::get('/tables', [\App\Http\Controllers\Api\DiningTableController::class, 'index']);
        Route::get('/tables/{tableId}', [\App\Http\Controllers\Api\DiningTableController::class, 'show']);
        Route::post('/tables', [\App\Http\Controllers\Api\DiningTableController::class, 'store']);

        // Orders
        Route::post('/orders', [\App\Http\Controllers\Api\OrderController::class, 'store']);
        Route::get('/tables/{tableId}/orders', [\App\Http\Controllers\Api\OrderController::class, 'indexByTable']);
        Route::get('/orders/{orderId}', [\App\Http\Controllers\Api\OrderController::class, 'show']);
        Route::put('/orders/{orderId}', [\App\Http\Controllers\Api\OrderController::class, 'update']);
        Route::post('/orders/{orderId}/pay', [\App\Http\Controllers\Api\OrderController::class, 'pay']);

        // Menu
        Route::get('/menu/items', [\App\Http\Controllers\Api\MenuController::class, 'index']);
        Route::post('/menu/items', [\App\Http\Controllers\Api\MenuController::class, 'store']);
        Route::put('/menu/items/{itemId}', [\App\Http\Controllers\Api\MenuController::class, 'update']);
        Route::get('/menu/categories', [\App\Http\Controllers\Api\MenuController::class, 'categories']);
        Route::post('/menu/categories', [\App\Http\Controllers\Api\MenuController::class, 'storeCategory']);
    });
});
