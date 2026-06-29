<?php

namespace App\Http\Controllers\Api;

use App\Attributes\OpenApi\Delete;
use App\Attributes\OpenApi\Get;
use App\Attributes\OpenApi\Post;
use App\Attributes\OpenApi\Put;
use App\Http\Controllers\Controller;
use App\Models\DiningTable;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderAddition;
use App\Models\OrderItem;
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
                        new OA\Property(property: "quantity", type: "integer", example: 2),
                        new OA\Property(property: "note", type: "string", example: "Sin cebolla", description: "Nota o indicación especial para este ítem")
                    ]
                )),
                new OA\Property(property: "nombre_mozo", type: "string", example: "Carlos", description: "Nombre del mozo temporal")
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
            'items.*.note' => 'nullable|string|max:255',
            'nombre_mozo' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($request) {
            $table = DiningTable::findOrFail($request->tableId);

            $order = Order::create([
                'dining_table_id' => $table->id,
                'user_id' => $request->user()->id,
                'nombre_mozo' => $request->input('nombre_mozo'),
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
                    'note' => $itemData['note'] ?? null,
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
                    'nombre_mozo' => $order->nombre_mozo,
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
                    $notes = $group->pluck('note')->filter()->unique()->values()->implode(', ');
                    return [
                        'name'     => $group->first()->menuItem->name,
                        'quantity' => $group->sum('quantity'),
                        'price'    => $group->first()->price,
                        'subtotal' => $group->sum('subtotal'),
                        'note'     => $notes ?: null,
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

    #[Get("/mozo/{nombreMozo}/orders", "Listar pedidos activos de un mozo específico", "Pedidos", true, [
        new OA\Parameter(name: "nombreMozo", in: "path", required: true, schema: new OA\Schema(type: "string"))
    ])]
    public function mozoOrders(Request $request, $nombreMozo)
    {
        $orders = Order::where('nombre_mozo', $nombreMozo)
            ->where('status', 'pending')
            ->with(['items.menuItem', 'diningTable'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($order) {
                $order->summary = $order->items->groupBy('menu_item_id')->map(function ($group) {
                    $notes = $group->pluck('note')->filter()->unique()->values()->implode(', ');
                    return [
                        'name'     => $group->first()->menuItem->name,
                        'quantity' => $group->sum('quantity'),
                        'price'    => $group->first()->price,
                        'subtotal' => $group->sum('subtotal'),
                        'note'     => $notes ?: null,
                    ];
                })->values();
                return $order;
            });

        return response()->json([
            'success' => true,
            'data' => [
                'nombre_mozo' => $nombreMozo,
                'orders' => $orders
            ]
        ]);
    }

    #[Get("/orders/history", "Listar historial de pedidos con filtros", "Pedidos", true, [
        new OA\Parameter(name: "date", in: "query", required: false, schema: new OA\Schema(type: "string", format: "date"), description: "Filtrar por fecha exacta (YYYY-MM-DD)"),
        new OA\Parameter(name: "start_date", in: "query", required: false, schema: new OA\Schema(type: "string", format: "date")),
        new OA\Parameter(name: "end_date", in: "query", required: false, schema: new OA\Schema(type: "string", format: "date")),
        new OA\Parameter(name: "user_id", in: "query", required: false, schema: new OA\Schema(type: "integer"))
    ])]
    public function history(Request $request)
    {
        $request->validate([
            'date'       => 'nullable|date_format:Y-m-d',
            'start_date' => 'nullable|date_format:Y-m-d',
            'end_date'   => 'nullable|date_format:Y-m-d|after_or_equal:start_date',
            'user_id'    => 'nullable|integer|exists:users,id',
        ]);

        $query = Order::with(['diningTable', 'user', 'items'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        } else {
            if ($request->filled('start_date')) {
                $query->whereDate('created_at', '>=', $request->start_date);
            }
            if ($request->filled('end_date')) {
                $query->whereDate('created_at', '<=', $request->end_date);
            }
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $orders = $query->get()->map(function ($order) {
            return [
                'id'               => $order->id,
                'fecha_hora'       => $order->created_at->format('Y-m-d H:i:s'),
                'mesa'             => $order->diningTable?->name,
                'mozo'             => $order->nombre_mozo ?? $order->user?->name,
                'cantidad_pedidos' => $order->items->sum('quantity'),
                'items_count'      => $order->items->groupBy('menu_item_id')->count(),
                'monto'            => (float) $order->total_amount,
                'status'           => $order->status,
                'print_url'        => "/api/v1/orders/{$order->id}/print",
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => ['orders' => $orders],
        ]);
    }

    #[Get("/orders/{orderId}/print", "Obtener ticket de impresión de un pedido", "Pedidos", true, [
        new OA\Parameter(name: "orderId", in: "path", required: true, schema: new OA\Schema(type: "integer"))
    ])]
    public function printTicket(Request $request, $id)
    {

        /** @var Order $order */
        $order = Order::with(['items.menuItem', 'diningTable', 'user'])->findOrFail($id);

        $items = $order->items->groupBy('menu_item_id')->map(function ($group) {
            $notes = $group->pluck('note')->filter()->unique()->values()->implode(', ');
            return [
                'name'     => $group->first()->menuItem->name,
                'quantity' => $group->sum('quantity'),
                'price'    => (float) $group->first()->price,
                'subtotal' => (float) $group->sum('subtotal'),
                'note'     => $notes ?: null,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data'    => [
                'ticket' => [
                    'order_id'   => $order->id,
                    'fecha_hora' => $order->created_at->format('Y-m-d H:i:s'),
                    'mesa'       => $order->diningTable?->name,
                    'mozo'       => $order->nombre_mozo ?? $order->user?->name,
                    'status'     => $order->status,
                    'items'      => $items,
                    'total'      => (float) $order->total_amount,
                ],
            ],
        ]);
    }

    #[Get("/orders/{orderId}", "Obtener detalles de un pedido", "Pedidos", true, [
        new OA\Parameter(name: "orderId", in: "path", required: true, schema: new OA\Schema(type: "integer"))
    ])]
    public function show(Request $request, $id)
    {

        /** @var Order $order */
        $order = Order::with(['items.menuItem', 'user'])->findOrFail($id);

        $summary = $order->items->groupBy('menu_item_id')->map(function ($group) {
            $notes = $group->pluck('note')->filter()->unique()->values()->implode(', ');
            return [
                'name'     => $group->first()->menuItem->name,
                'quantity' => $group->sum('quantity'),
                'price'    => $group->first()->price,
                'subtotal' => $group->sum('subtotal'),
                'note'     => $notes ?: null,
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

    #[Post("/orders/{orderId}/aumento", "Aumentar ítems en un pedido (registra en order_additions)", "Pedidos", true, new OA\RequestBody(
        content: new OA\JsonContent(
            required: ["items"],
            properties: [
                new OA\Property(property: "items", type: "array", items: new OA\Items(
                    properties: [
                        new OA\Property(property: "menuItemId", type: "integer", example: 1),
                        new OA\Property(property: "quantity", type: "integer", example: 2),
                        new OA\Property(property: "note", type: "string", example: "Sin cebolla", description: "Nota o indicación especial para este ítem")
                    ]
                ))
            ]
        )
    ))]
    public function aumento(Request $request, $id)
    {
        $request->validate([
            'items'                => 'required|array|min:1',
            'items.*.menuItemId'   => 'required|exists:menu_items,id',
            'items.*.quantity'     => 'required|integer|min:1',
            'items.*.note'         => 'nullable|string|max:255',
        ]);

        return DB::transaction(function () use ($request, $id) {
            $order = Order::findOrFail($id);

            if ($order->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede modificar un pedido que no esté pendiente'
                ], 400);
            }

            $addedItems  = [];
            $totalAmount = $order->total_amount;

            foreach ($request->items as $itemData) {
                $menuItem = MenuItem::findOrFail($itemData['menuItemId']);
                $subtotal = $menuItem->price * $itemData['quantity'];

                $orderItem = OrderItem::create([
                    'order_id'     => $order->id,
                    'menu_item_id' => $menuItem->id,
                    'quantity'     => $itemData['quantity'],
                    'price'        => $menuItem->price,
                    'subtotal'     => $subtotal,
                    'note'         => $itemData['note'] ?? null,
                ]);

                $totalAmount += $subtotal;
                $addedItems[] = $orderItem->load('menuItem');
            }

            $order->update(['total_amount' => $totalAmount]);

            // Registrar el aumento en la tabla order_additions (para impresión incremental)
            OrderAddition::create([
                'order_id'        => $order->id,
                'dining_table_id' => $order->dining_table_id,
                'items'           => $addedItems,
            ]);

            return response()->json([
                'success'    => true,
                'message'    => 'Ítems agregados y registrados en order_additions',
                'data'       => [
                    'order'      => $order->fresh(['items.menuItem', 'user']),
                    'addedItems' => $addedItems,
                ]
            ]);
        });
    }

    #[Delete("/orders/{orderId}", "Eliminar pedido de una mesa", "Pedidos", true, [], [
        new OA\Parameter(name: "orderId", in: "path", required: true, schema: new OA\Schema(type: "integer"))
    ])]
    public function destroy(Request $request, $id)
    {
        return DB::transaction(function () use ($id) {
            $order = Order::with('diningTable')->findOrFail($id);

            if ($order->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden eliminar pedidos en estado pendiente'
                ], 400);
            }

            $table = $order->diningTable;
            $order->delete();

            $hasOtherPending = Order::where('dining_table_id', $table->id)
                ->where('status', 'pending')
                ->exists();

            if (!$hasOtherPending) {
                $table->update(['status' => 'free']);
            }

            return response()->json([
                'success' => true,
                'message' => 'Pedido eliminado exitosamente'
            ]);
        });
    }

    #[Post("/orders/{orderId}/reduccion", "Reducir cantidades de ítems en un pedido (actualiza order_items)", "Pedidos", true, new OA\RequestBody(
        content: new OA\JsonContent(
            required: ["items"],
            properties: [
                new OA\Property(property: "items", type: "array", items: new OA\Items(
                    properties: [
                        new OA\Property(property: "menuItemId", type: "integer", example: 1),
                        new OA\Property(property: "quantity",   type: "integer", example: 1,
                            description: "Nueva cantidad total deseada. Si es 0 o menor al mínimo se elimina el ítem.")
                    ]
                ))
            ]
        )
    ))]
    public function reduccion(Request $request, $id)
    {
        $request->validate([
            'items'                => 'required|array|min:1',
            'items.*.menuItemId'   => 'required|exists:menu_items,id',
            'items.*.quantity'     => 'required|integer|min:0',
        ]);

        return DB::transaction(function () use ($request, $id) {
            $order = Order::findOrFail($id);

            if ($order->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede modificar un pedido que no esté pendiente'
                ], 400);
            }

            $reducedItems = [];
            $totalAmount  = $order->total_amount;

            foreach ($request->items as $itemData) {
                $menuItemId   = $itemData['menuItemId'];
                $newQuantity  = (int) $itemData['quantity'];

                // Sumar todas las filas existentes de este ítem en order_items
                $existingRows = OrderItem::where('order_id', $order->id)
                    ->where('menu_item_id', $menuItemId)
                    ->get();

                if ($existingRows->isEmpty()) {
                    continue; // Ítem no existe en la orden, ignorar
                }

                $currentQuantity = $existingRows->sum('quantity');
                $menuItem        = MenuItem::findOrFail($menuItemId);

                if ($newQuantity <= 0) {
                    // Eliminar todas las filas de este ítem
                    $removed      = $existingRows->sum('subtotal');
                    $totalAmount -= $removed;
                    OrderItem::where('order_id', $order->id)
                        ->where('menu_item_id', $menuItemId)
                        ->delete();

                    $reducedItems[] = [
                        'menuItemId' => $menuItemId,
                        'name'       => $menuItem->name,
                        'action'     => 'deleted',
                        'previous'   => $currentQuantity,
                        'current'    => 0,
                    ];
                } elseif ($newQuantity < $currentQuantity) {
                    // Reducir: eliminar todas las filas antiguas y crear una nueva con la cantidad correcta
                    $oldSubtotal  = $existingRows->sum('subtotal');
                    $newSubtotal  = $menuItem->price * $newQuantity;
                    $totalAmount  = $totalAmount - $oldSubtotal + $newSubtotal;

                    OrderItem::where('order_id', $order->id)
                        ->where('menu_item_id', $menuItemId)
                        ->delete();

                    OrderItem::create([
                        'order_id'     => $order->id,
                        'menu_item_id' => $menuItemId,
                        'quantity'     => $newQuantity,
                        'price'        => $menuItem->price,
                        'subtotal'     => $newSubtotal,
                    ]);

                    $reducedItems[] = [
                        'menuItemId' => $menuItemId,
                        'name'       => $menuItem->name,
                        'action'     => 'reduced',
                        'previous'   => $currentQuantity,
                        'current'    => $newQuantity,
                    ];
                }
                // Si newQuantity >= currentQuantity, no se hace reducción (se ignora)
            }

            $order->update(['total_amount' => $totalAmount]);

            return response()->json([
                'success'      => true,
                'message'      => 'Reducción aplicada sobre order_items',
                'data'         => [
                    'order'        => $order->fresh(['items.menuItem', 'user']),
                    'reducedItems' => $reducedItems,
                ]
            ]);
        });
    }
}
