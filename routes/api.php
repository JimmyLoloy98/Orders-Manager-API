<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\DiningTableController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\MenuController;
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
        Route::get('/tables', [DiningTableController::class, 'index']);
        Route::put('/tables/{id}', [DiningTableController::class, 'update']);
        Route::patch('/tables/{id}', [DiningTableController::class, 'update']);
        Route::get('/tables/{id}', [DiningTableController::class, 'show']);
        Route::post('/tables', [DiningTableController::class, 'store']);

        // Orders
        Route::get('/tables/{tableId}/orders', [OrderController::class, 'indexByTable']);
        Route::post('/orders', [OrderController::class, 'store']);
        Route::get('/orders/{orderId}', [OrderController::class, 'show']);
        Route::put('/orders/{orderId}', [OrderController::class, 'update']);
        Route::post('/orders/{orderId}/pay', [OrderController::class, 'pay']);

        // Menu
        Route::get('/menu/items', [MenuController::class, 'index']);
        Route::post('/menu/items', [MenuController::class, 'store']);
        Route::put('/menu/items/{itemId}', [MenuController::class, 'update']);
        Route::get('/menu/categories', [MenuController::class, 'categories']);
        Route::post('/menu/categories', [MenuController::class, 'storeCategory']);
    });
});
