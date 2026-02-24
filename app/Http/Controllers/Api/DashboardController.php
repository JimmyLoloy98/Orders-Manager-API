<?php

namespace App\Http\Controllers\Api;

use App\Attributes\OpenApi\Get;
use App\Http\Controllers\Controller;
use App\Models\DiningTable;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class DashboardController extends Controller
{
    #[Get("/dashboard/stats", "Obtener estadísticas generales del dashboard", "Dashboard")]
    public function stats(Request $request)
    {
        $today = Carbon::today();

        $totalOrders = Order::whereDate('created_at', $today)
            ->count();

        $revenue = Order::where('status', 'paid')
            ->whereDate('created_at', $today)
            ->sum('total_amount');

        $activeTables = DiningTable::where('status', 'busy')
            ->count();

        $averageOrder = $totalOrders > 0 ? (float)$revenue / $totalOrders : 0;

        $topDish = OrderItem::whereHas('order', function ($query) use ($today) {
                $query->whereDate('created_at', $today);
            })
            ->select('menu_item_id', DB::raw('SUM(quantity) as total_sold'))
            ->groupBy('menu_item_id')
            ->orderByDesc('total_sold')
            ->with('menuItem:id,name')
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'totalOrders' => $totalOrders,
                'revenue' => (float)$revenue,
                'activeTables' => $activeTables,
                'averageOrder' => (float)$averageOrder,
                'topDish' => $topDish ? [
                    'name' => $topDish->menuItem->name,
                    'totalSold' => (int)$topDish->total_sold
                ] : null,
            ]
        ]);
    }

    #[Get("/dashboard/recent-activity", "Obtener actividad reciente", "Dashboard", true, [
        new OA\Parameter(name: "limit", in: "query", required: false, schema: new OA\Schema(type: "integer", default: 10)),
    ])]
    public function recentActivity(Request $request)
    {
        $limit = $request->query('limit', 10);

        $activities = Order::with(['diningTable:id,name'])
            ->orderBy('updated_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'type' => $order->status === 'paid' ? 'payment' : 'order',
                    'tableName' => $order->diningTable->name,
                    'description' => $order->status === 'paid'
                        ? "Pedido pagado en {$order->diningTable->name}"
                        : "Nuevo pedido en {$order->diningTable->name}",
                    'amount' => (float)$order->total_amount,
                    'date' => $order->updated_at->toISOString(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'activities' => $activities,
            ]
        ]);
    }

    #[Get("/dashboard/monthly-overview", "Resumen mensual para gráficas", "Dashboard", true, [
        new OA\Parameter(name: "months", in: "query", required: false, schema: new OA\Schema(type: "integer", default: 6)),
    ])]
    public function monthlyOverview(Request $request)
    {
        $monthsCount = $request->query('months', 6);

        $startDate = Carbon::now()->subMonths($monthsCount - 1)->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();

        $monthlyStats = Order::where('status', 'paid')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select(
                DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month"),
                DB::raw('SUM(total_amount) as revenue'),
                DB::raw('COUNT(*) as orderCount')
            )
            ->groupBy('month')
            ->get()
            ->keyBy('month');

        $data = [];
        $current = $startDate->copy();

        while ($current->lte($endDate)) {
            $monthKey = $current->format('Y-m');
            $stat = $monthlyStats->get($monthKey);

            $data[] = [
                'month' => $current->format('M Y'),
                'revenue' => (float)($stat->revenue ?? 0),
                'orderCount' => (int)($stat->orderCount ?? 0),
            ];
            $current->addMonth();
        }

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }
}
