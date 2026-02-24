<?php

namespace App\Http\Controllers\Api;

use App\Attributes\OpenApi\Get;
use App\Attributes\OpenApi\Post;
use App\Http\Controllers\Controller;
use App\Models\DiningTable;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class DiningTableController extends Controller
{
    #[Get("/tables", "Listar mesas y su estado", "Mesas", true, [
        new OA\Parameter(name: "status", in: "query", required: false, schema: new OA\Schema(type: "string", enum: ["free", "busy"]), description: "Filtrar por estado"),
        new OA\Parameter(name: "limit", in: "query", required: false, schema: new OA\Schema(type: "integer", default: 50)),
        new OA\Parameter(name: "page", in: "query", required: false, schema: new OA\Schema(type: "integer", default: 1)),
    ])]
    public function index(Request $request)
    {
        $companyId = $request->user()->company_id;
        $status = $request->query('status');
        $limit = $request->query('limit', 50);

        $query = DiningTable::where('company_id', $companyId);

        if ($status) {
            $query->where('status', $status);
        }

        $tables = $query->paginate($limit);

        return response()->json([
            'success' => true,
            'data' => [
                'tables' => $tables->items(),
                'pagination' => [
                    'total' => $tables->total(),
                    'page' => $tables->currentPage(),
                    'limit' => $tables->perPage(),
                ]
            ]
        ]);
    }

    #[Get("/tables/{tableId}", "Obtener detalles de una mesa", "Mesas", true, [
        new OA\Parameter(name: "tableId", in: "path", required: true, schema: new OA\Schema(type: "integer"))
    ])]
    public function show(Request $request, $id)
    {
        $companyId = $request->user()->company_id;
        $table = DiningTable::where('company_id', $companyId)->find($id);

        if (!$table) {
            return response()->json([
                'success' => false,
                'message' => 'Mesa no encontrada'
            ], 404);
        }

        // Load active orders and their items
        $table->load(['activeOrders.items.menuItem']);

        $currentOrders = $table->activeOrders->map(function ($order) {
            return [
                'id' => $order->id,
                'items' => $order->items->map(function ($item) {
                    return [
                        'name' => $item->menuItem->name,
                        'quantity' => $item->quantity,
                        'pricePerUnit' => (float)$item->price,
                        'subtotal' => (float)$item->subtotal,
                    ];
                }),
                'totalAmount' => (float)$order->total_amount,
                'createdAt' => $order->created_at->toISOString(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $table->id,
                'name' => $table->name,
                'status' => $table->status,
                'currentOrders' => $currentOrders,
                'totalAmount' => (float)$table->activeOrders->sum('total_amount'),
            ]
        ]);
    }

    #[Post("/tables", "Crear una mesa", "Mesas", true, new OA\RequestBody(
        content: new OA\JsonContent(
            required: ["name"],
            properties: [
                new OA\Property(property: "name", type: "string", example: "M3")
            ]
        )
    ))]
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $companyId = $request->user()->company_id;

        $table = DiningTable::create([
            'company_id' => $companyId,
            'name' => $request->name,
            'status' => 'free',
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $table->id,
                'name' => $table->name,
                'status' => $table->status,
            ]
        ], 201);
    }
}
