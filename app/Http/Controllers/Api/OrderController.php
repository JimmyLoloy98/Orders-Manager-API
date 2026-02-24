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

        $companyId = $request->user()->company_id;

        return DB::transaction(function () use ($request, $companyId) {
            $table = DiningTable::where('company_id', $companyId)->findOrFail($request->tableId);

            $order = Order::create([
                'company_id' => $companyId,
                'dining_table_id' => $table->id,
                'status' => 'pending',
                'total_amount' => 0,
            ]);

            $totalAmount = 0;

            foreach ($request->items as $itemData) {
                $menuItem = MenuItem::where('company_id', $companyId)->findOrFail($itemData['menuItemId']);
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
        $companyId = $request->user()->company_id;
        $table = DiningTable::where('company_id', $companyId)->findOrFail($tableId);

        $orders = Order::where('dining_table_id', $table->id)
            ->with('items.menuItem')
            ->orderBy('created_at', 'desc')
            ->get();

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
        $companyId = $request->user()->company_id;
        $order = Order::where('company_id', $companyId)
            ->with('items.menuItem')
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $order
        ]);
    }

    #[Put("/orders/{orderId}", "Actualizar un pedido", "Pedidos", true, new OA\RequestBody(
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "items", type: "array", items: new OA\Items(
                    properties: [
                        new OA\Property(property: "menuItemId", type: "integer"),
                        new OA\Property(property: "quantity", type: "integer")
                    ]
                ))
            ]
        )
    ))]
    public function update(Request $request, $id)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.menuItemId' => 'required|exists:menu_items,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $companyId = $request->user()->company_id;

        return DB::transaction(function () use ($request, $companyId, $id) {
            $order = Order::where('company_id', $companyId)->findOrFail($id);

            if ($order->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede modificar un pedido que no esté pendiente'
                ], 400);
            }

            // Remove old items and add new ones (Simplified logic)
            $order->items()->delete();

            $totalAmount = 0;
            foreach ($request->items as $itemData) {
                $menuItem = MenuItem::where('company_id', $companyId)->findOrFail($itemData['menuItemId']);
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

            return response()->json([
                'success' => true,
                'data' => $order->fresh('items.menuItem')
            ]);
        });
    }

    #[Post("/orders/{orderId}/pay", "Marcar pedido como pagado", "Pedidos", true)]
    public function pay(Request $request, $id)
    {
        $companyId = $request->user()->company_id;

        return DB::transaction(function () use ($companyId, $id) {
            $order = Order::where('company_id', $companyId)->findOrFail($id);

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
