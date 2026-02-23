<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\CreditController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\OriginController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ScrapController;
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
        Route::post('/clients', [ClientController::class, 'store']);
        Route::get('/clients/{id}', [ClientController::class, 'show']);
        Route::put('/clients/{id}', [ClientController::class, 'update']);
        Route::delete('/clients/{id}', [ClientController::class, 'destroy']);
        Route::get('/clients/{id}/summary', [ClientController::class, 'summary']);
        Route::post('/clients/{id}/upload-photo', [ClientController::class, 'uploadPhoto']);
        Route::delete('/clients/{id}/photo', [ClientController::class, 'deletePhoto']);

        // Credits
        Route::get('/credits', [CreditController::class, 'index']);
        Route::post('/credits', [CreditController::class, 'store']);
        Route::get('/credits/{id}', [CreditController::class, 'show']);
        Route::put('/credits/{id}', [CreditController::class, 'update']);
        Route::get('/clients/{clientId}/credits', [CreditController::class, 'clientCredits']);

        // Payments
        Route::get('/payments', [PaymentController::class, 'index']);
        Route::post('/payments', [PaymentController::class, 'store']);
        Route::get('/payments/{id}', [PaymentController::class, 'show']);
        Route::put('/payments/{id}', [PaymentController::class, 'update']);
        Route::get('/clients/{clientId}/payments', [PaymentController::class, 'clientPayments']);

        // Scraps
        Route::get('/scraps', [ScrapController::class, 'index']);
        Route::post('/scraps', [ScrapController::class, 'store']);
        Route::get('/scraps/{id}', [ScrapController::class, 'show']);
        Route::put('/scraps/{id}', [ScrapController::class, 'update']);
        Route::delete('/scraps/{id}', [ScrapController::class, 'destroy']);

        // Origins
        Route::get('/origins', [OriginController::class, 'index']);
        Route::post('/origins', [OriginController::class, 'store']);
        Route::get('/origins/{id}', [OriginController::class, 'show']);
        Route::put('/origins/{id}', [OriginController::class, 'update']);
        Route::delete('/origins/{id}', [OriginController::class, 'destroy']);
    });
});
