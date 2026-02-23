<?php

namespace App\Http\Controllers\Api;

use App\Attributes\OpenApi\Get;
use App\Helpers\OpenApiHelper;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Credit;
use App\Models\Payment;
use App\Models\PaymentItem;
use App\Models\Scrap;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    #[Get("/dashboard/stats", "Obtener estadísticas generales del dashboard", "Dashboard")]
    public function stats(Request $request)
    {
        $companyId = $request->user()->company_id;

        $totalActiveClients = Client::where('company_id', $companyId)
            ->where('current_debt', '>', 0)
            ->count();

        $totalCreditExtended = Credit::where('company_id', $companyId)
            ->sum('amount');

        $totalScrapPaymentsReceived = Payment::where('company_id', $companyId)
            ->sum('total_value');

        $totalPendingDebt = Client::where('company_id', $companyId)
            ->sum('current_debt');

        return response()->json([
            'totalActiveClients' => $totalActiveClients,
            'totalCreditExtended' => (float) $totalCreditExtended,
            'totalScrapPaymentsReceived' => (float) $totalScrapPaymentsReceived,
            'totalPendingDebt' => (float) $totalPendingDebt,
        ]);
    }

    #[Get(
        "/dashboard/recent-activity",
        "Obtener actividad reciente (créditos y pagos)",
        "Dashboard",
        true,
        [
            new \OpenApi\Attributes\Parameter(name: "limit", in: "query", required: false, schema: new \OpenApi\Attributes\Schema(type: "integer", default: 10), description: "Número de registros a retornar"),
        ]
    )]
    public function recentActivity(Request $request)
    {
        $companyId = $request->user()->company_id;
        $limit = $request->query('limit', 10);

        // Get recent credits
        $credits = Credit::where('company_id', $companyId)
            ->with('client:id,business_name')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($credit) {
                return [
                    'id' => $credit->id,
                    'type' => 'credit',
                    'clientName' => $credit->client->business_name,
                    'description' => 'Crédito por ' . number_format($credit->amount, 2),
                    'amount' => (float) $credit->amount,
                    'date' => $credit->date->toISOString(),
                ];
            });

        // Get recent payments
        $payments = Payment::where('company_id', $companyId)
            ->with('client:id,business_name')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'type' => 'payment',
                    'clientName' => $payment->client->business_name,
                    'description' => 'Pago con chatarra por ' . number_format($payment->total_value, 2),
                    'amount' => (float) $payment->total_value,
                    'date' => $payment->date->toISOString(),
                ];
            });

        // Merge and sort by date
        $activities = $credits->concat($payments)
            ->sortByDesc('date')
            ->take($limit)
            ->values();

        return response()->json([
            'activities' => $activities,
        ]);
    }

    #[Get(
        "/dashboard/monthly-overview",
        "Obtener datos para el gráfico de resumen mensual",
        "Dashboard",
        true,
        [
            new \OpenApi\Attributes\Parameter(name: "months", in: "query", required: false, schema: new \OpenApi\Attributes\Schema(type: "integer", default: 6), description: "Número de meses a retornar"),
        ]
    )]
    public function monthlyOverview(Request $request)
    {
        $companyId = $request->user()->company_id;
        $months = $request->query('months', 6);

        $startDate = Carbon::now()->subMonths($months - 1)->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();

        // Get credits by month
        $creditsByMonth = Credit::where('company_id', $companyId)
            ->whereBetween('date', [$startDate, $endDate])
            ->select(
                DB::raw("DATE_FORMAT(date, '%Y-%m') as month"),
                DB::raw('SUM(amount) as total')
            )
            ->groupBy('month')
            ->pluck('total', 'month')
            ->toArray();

        // Get payments by month
        $paymentsByMonth = Payment::where('company_id', $companyId)
            ->whereBetween('date', [$startDate, $endDate])
            ->select(
                DB::raw("DATE_FORMAT(date, '%Y-%m') as month"),
                DB::raw('SUM(total_value) as total')
            )
            ->groupBy('month')
            ->pluck('total', 'month')
            ->toArray();

        // Generate data for all months
        $data = [];
        $current = $startDate->copy();

        while ($current->lte($endDate)) {
            $monthKey = $current->format('Y-m');
            $data[] = [
                'month' => $current->format('M'),
                'credits' => (float) ($creditsByMonth[$monthKey] ?? 0),
                'payments' => (float) ($paymentsByMonth[$monthKey] ?? 0),
            ];
            $current->addMonth();
        }

        return response()->json([
            'data' => $data,
        ]);
    }

    #[Get(
        "/dashboard/scrap-collection",
        "Estadísticas de recolección de chatarra por periodo",
        "Dashboard",
        true,
        [
            new \OpenApi\Attributes\Parameter(name: "startDate", in: "query", required: true, schema: new \OpenApi\Attributes\Schema(type: "string", format: "date"), description: "Fecha inicio (YYYY-MM-DD)"),
            new \OpenApi\Attributes\Parameter(name: "endDate", in: "query", required: true, schema: new \OpenApi\Attributes\Schema(type: "string", format: "date"), description: "Fecha fin (YYYY-MM-DD)"),
        ]
    )]
    public function scrapCollection(Request $request)
    {
        $request->validate([
            'startDate' => 'required|date',
            'endDate' => 'required|date',
        ]);

        $companyId = $request->user()->company_id;
        $startDate = $request->query('startDate');
        $endDate = $request->query('endDate');

        $query = PaymentItem::whereHas('payment', function ($q) use ($companyId, $startDate, $endDate) {
            $q->where('company_id', $companyId)
                ->whereBetween('date', [$startDate, $endDate]);
        })->with('scrap:id,name,unit_measure');

        $stats = $query->select('scrap_id', DB::raw('SUM(quantity) as total_quantity'))
            ->groupBy('scrap_id')
            ->get()
            ->map(function ($item) {
                return [
                    'scrapName' => $item->scrap?->name ?? 'N/A',
                    'unitMeasure' => $item->scrap?->unit_measure ?? 'N/A',
                    'totalQuantity' => (float) $item->total_quantity,
                ];
            });

        return response()->json([
            'period' => [
                'startDate' => $startDate,
                'endDate' => $endDate,
            ],
            'data' => $stats,
        ]);
    }
}
