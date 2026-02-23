<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HealthController extends Controller
{
    /**
     * Check API health status
     */
    public function check()
    {
        try {
            // Prueba conexión a base de datos
            DB::connection()->getPdo();
            $db_status = 'connected';
        } catch (\Exception $e) {
            Log::error('Database connection failed: ' . $e->getMessage());
            $db_status = 'disconnected';
        }

        return response()->json([
            'status' => $db_status === 'connected' ? 'healthy' : 'unhealthy',
            'database' => $db_status,
            'timestamp' => now()->toISOString(),
        ], $db_status === 'connected' ? 200 : 503);
    }
}
