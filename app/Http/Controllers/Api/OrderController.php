<?php

namespace App\Http\Controllers\Api;

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
                        new OA\Property(property: "quantity", type: "integer", example: 2)
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
