<?php

namespace App\Http\Controllers\Api;

use App\Attributes\OpenApi\Get;
use App\Attributes\OpenApi\Post;
use App\Attributes\OpenApi\Put;
use App\Http\Controllers\Controller;
use App\Models\DiningTable;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderUpdate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class OrderController extends Controller
{
    #[Post("/orders", "Crear pedido para una mesa", "Pedidos", true, new OA\RequestBody(
        content: new OA\JsonContent(
            required: ["tableId", "items"],
            properties: [
                new OA\Property(property: "tableId", type: "integer", example: 1),
                new OA\Property(property: "items", type: "array", items: new OA\Items(
                    properties: [
                        new OA\Property(property: "menuItemId", type: "integer", example: 1),
                        new OA\Property(property: "quantity", type: "integer", example: 2)
                    ]
                ))
            ]
        )
    ))]
    public function store(Request $request)
    {
        $request->validate([
            'tableId' => 'required|exists:dining_tables,id',
            'items' => 'required|array|min:1',
            'items.*.menuItemId' => 'required|exists:menu_items,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        return DB::transaction(function () use ($request) {
            $table = DiningTable::findOrFail($request->tableId);

            $order = Order::create([
                'dining_table_id' => $table->id,
                'user_id' => $request->user()->id,
                'status' => 'pending',
                'total_amount' => 0,
            ]);

            $totalAmount = 0;

            foreach ($request->items as $itemData) {
                $menuItem = MenuItem::findOrFail($itemData['menuItemId']);
                $subtotal = $menuItem->price * $itemData['quantity'];

                OrderItem::create([
                    'order_id' => $order->id,
                    'menu_item_id' => $menuItem->id,
                    'quantity' => $itemData['quantity'],
                    'price' => $menuItem->price,
                    'subtotal' => $subtotal,
                ]);

                $totalAmount += $subtotal;
            }

            $order->update(['total_amount' => $totalAmount]);
            $table->update(['status' => 'busy']);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $order->id,
                    'tableId' => $table->id,
                    'totalAmount' => (float)$totalAmount,
                    'createdAt' => $order->created_at->toISOString(),
                ]
            ], 201);
        });
    }

    #[Get("/tables/{tableId}/orders", "Listar pedidos de una mesa", "Pedidos", true, [
        new OA\Parameter(name: "tableId", in: "path", required: true, schema: new OA\Schema(type: "integer"))
    ])]
    public function indexByTable(Request $request, $tableId)
    {
        $table = DiningTable::findOrFail($tableId);

        $orders = Order::where('dining_table_id', $table->id)
            ->with(['items.menuItem', 'user'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($order) {
                $order->summary = $order->items->groupBy('menu_item_id')->map(function ($group) {
                    return [
                        'name' => $group->first()->menuItem->name,
                        'quantity' => $group->sum('quantity'),
                        'price' => $group->first()->price,
                        'subtotal' => $group->sum('subtotal')
                    ];
                })->values();
                return $order;
            });

        return response()->json([
            'success' => true,
            'data' => [
                'tableId' => $table->id,
                'orders' => $orders
            ]
        ]);
    }

    #[Get("/orders/{orderId}", "Obtener detalles de un pedido", "Pedidos", true, [
        new OA\Parameter(name: "orderId", in: "path", required: true, schema: new OA\Schema(type: "integer"))
    ])]
    public function show(Request $request, $id)
    {
        $order = Order::with(['items.menuItem', 'user'])->findOrFail($id);

        $summary = $order->items->groupBy('menu_item_id')->map(function ($group) {
            return [
                'name' => $group->first()->menuItem->name,
                'quantity' => $group->sum('quantity'),
                'price' => $group->first()->price,
                'subtotal' => $group->sum('subtotal')
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => [
                'order' => $order,
                'summary' => $summary
            ]
        ]);
    }

    #[Put("/orders/{orderId}", "Actualizar pedido existente", "Pedidos", true, new OA\RequestBody(
        content: new OA\JsonContent(
            required: ["items"],
            properties: [
                new OA\Property(property: "items", type: "array", items: new OA\Items(
                    properties: [
                        new OA\Property(property: "menuItemId", type: "integer", example: 1),
                        new OA\Property(property: "quantity", type: "integer", example: 1)
                    ]
                ))
            ]
        )
    ), responses: [
        new OA\Response(
            response: 200,
            description: "Pedido actualizado exitosamente",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "success", type: "boolean"),
                    new OA\Property(property: "message", type: "string"),
                    new OA\Property(property: "data", properties: [
                        new OA\Property(property: "order", type: "object"),
                        new OA\Property(property: "addedItems", type: "array", items: new OA\Items(type: "object"))
                    ], type: "object")
                ]
            )
        )
    ])]
    public function update(Request $request, $id)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.menuItemId' => 'required|exists:menu_items,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        return DB::transaction(function () use ($request, $id) {
            $order = Order::findOrFail($id);

            if ($order->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede modificar un pedido que no esté pendiente'
                ], 400);
            }

            $newItems = [];
            $totalAmount = $order->total_amount;

            foreach ($request->items as $itemData) {
                $menuItem = MenuItem::findOrFail($itemData['menuItemId']);
                $subtotal = $menuItem->price * $itemData['quantity'];

                $orderItem = OrderItem::create([
                    'order_id' => $order->id,
                    'menu_item_id' => $menuItem->id,
                    'quantity' => $itemData['quantity'],
                    'price' => $menuItem->price,
                    'subtotal' => $subtotal,
                ]);

                $totalAmount += $subtotal;
                $newItems[] = $orderItem->load('menuItem');
            }

            $order->update(['total_amount' => $totalAmount]);

            // Save only this update (overwriting previous one)
            OrderUpdate::updateOrCreate(
                [
                    'order_id' => $order->id,
                    'dining_table_id' => $order->dining_table_id
                ],
                [
                    'items' => $newItems
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Pedido actualizado y agregados registrados',
                'data' => [
                    'order' => $order->fresh(['items.menuItem', 'user']),
                    'addedItems' => $newItems
                ]
            ]);
        });
    }

    #[Get("/orders/{orderId}/last-update", "Obtener la última actualización para imprimir", "Pedidos", true, [
        new OA\Parameter(name: "orderId", in: "path", required: true, schema: new OA\Schema(type: "integer"))
    ], responses: [
        new OA\Response(
            response: 200,
            description: "Última actualización recuperada",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "success", type: "boolean"),
                    new OA\Property(property: "data", type: "object")
                ]
            )
        ),
        new OA\Response(response: 404, description: "No hay actualizaciones recientes")
    ])]
    public function lastUpdate($orderId)
    {
        $update = OrderUpdate::where('order_id', $orderId)->first();

        if (!$update) {
            return response()->json([
                'success' => false,
                'message' => 'No hay actualizaciones recientes para este pedido'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $update
        ]);
    }

    #[Post("/orders/{orderId}/pay", "Marcar pedido como pagado", "Pedidos", true)]
    public function pay(Request $request, $id)
    {
        return DB::transaction(function () use ($id) {
            $order = Order::findOrFail($id);

            if ($order->status === 'paid') {
                return response()->json([
                    'success' => false,
                    'message' => 'El pedido ya ha sido pagado'
                ], 400);
            }

            $order->update(['status' => 'paid']);

            // Check if there are other pending orders for the table
            $table = $order->diningTable;
            $hasOtherPending = Order::where('dining_table_id', $table->id)
                ->where('status', 'pending')
                ->exists();

            if (!$hasOtherPending) {
                $table->update(['status' => 'free']);
            }

            return response()->json([
                'success' => true,
                'message' => 'Pedido pagado exitosamente'
            ]);
        });
    }
}
