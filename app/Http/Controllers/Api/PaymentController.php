<?php

namespace App\Http\Controllers\Api;

use App\Attributes\OpenApi\Get;
use App\Attributes\OpenApi\Post;
use App\Attributes\OpenApi\Put;
use App\Helpers\OpenApiHelper;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Payment;
use App\Models\PaymentItem;
use App\Models\Scrap;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    #[Get(
        "/payments",
        "Listar todos los pagos con chatarra",
        "Payments",
        true,
        [
            new \OpenApi\Attributes\Parameter(name: "page", in: "query", required: false, schema: new \OpenApi\Attributes\Schema(type: "integer", default: 1), description: "Número de página"),
            new \OpenApi\Attributes\Parameter(name: "limit", in: "query", required: false, schema: new \OpenApi\Attributes\Schema(type: "integer", default: 10), description: "Registros por página"),
            new \OpenApi\Attributes\Parameter(name: "clientId", in: "query", required: false, schema: new \OpenApi\Attributes\Schema(type: "string"), description: "Filtrar por cliente")
        ]
    )]
    public function index(Request $request)
    {
        $companyId = $request->user()->company_id;
        $query = Payment::where('company_id', $companyId)
            ->with(['client:id,business_name,origin', 'items.scrap:id,name,unit_measure']);

        // Filter by client
        if ($request->has('clientId')) {
            $query->where('client_id', $request->query('clientId'));
        }

        $page = $request->query('page', 1);
        $limit = $request->query('limit', 10);
        $total = $query->count();

        $payments = $query->skip(($page - 1) * $limit)
            ->take($limit)
            ->get()
            ->map(function ($payment) {
                return $this->formatPayment($payment);
            });

        return response()->json([
            'payments' => $payments,
            'total' => $total,
            'page' => (int) $page,
            'limit' => (int) $limit,
        ]);
    }

    #[Get(
        "/payments/{id}",
        "Obtener detalles de un pago específico",
        "Payments",
        true,
        [
            new \OpenApi\Attributes\Parameter(name: "id", in: "path", required: true, schema: new \OpenApi\Attributes\Schema(type: "string"), description: "ID del pago"),
        ]
    )]
    public function show(Request $request, $id)
    {
        $companyId = $request->user()->company_id;
        $payment = Payment::where('company_id', $companyId)
            ->with(['client:id,business_name,origin', 'items.scrap:id,name,unit_measure'])
            ->findOrFail($id);

        return response()->json($this->formatPayment($payment));
    }

    #[Post(
        "/payments",
        "Crear un nuevo pago con chatarra",
        "Payments",
        true,
        new \OpenApi\Attributes\RequestBody(
            required: true,
            content: new \OpenApi\Attributes\JsonContent(
                required: ["clientId", "date", "items"],
                properties: [
                    new \OpenApi\Attributes\Property(property: "clientId", type: "string", example: "1"),
                    new \OpenApi\Attributes\Property(property: "date", type: "string", format: "date", example: "2024-01-24"),
                    new \OpenApi\Attributes\Property(
                        property: "items",
                        type: "array",
                        items: new \OpenApi\Attributes\Items(
                            type: "object",
                            properties: [
                                new \OpenApi\Attributes\Property(property: "scrapId", type: "string", example: "1"),
                                new \OpenApi\Attributes\Property(property: "amount", type: "number", format: "float", example: 500.00),
                                new \OpenApi\Attributes\Property(property: "quantity", type: "number", format: "float", example: 10.5),
                            ]
                        )
                    ),
                    new \OpenApi\Attributes\Property(property: "notes", type: "string", nullable: true),
                ]
            )
        )
    )]
    public function store(Request $request)
    {
        $request->validate([
            'clientId' => 'required|exists:clients,id',
            'date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.scrapId' => 'required|exists:scraps,id',
            'items.*.amount' => 'required|numeric|min:0',
            'items.*.quantity' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $companyId = $request->user()->company_id;
        $clientId = $request->input('clientId', $request->input('client_id'));
        $client = Client::where('company_id', $companyId)->findOrFail($clientId);

        DB::beginTransaction();
        try {
            // Calculate total value (you might want to add price per scrap type)
            // For now, we'll use the amount directly
            $totalValue = collect($request->items)->sum('amount');

            $payment = Payment::create([
                'company_id' => $companyId,
                'client_id' => $clientId,
                'date' => $request->date,
                'total_value' => $totalValue,
                'notes' => $request->notes,
            ]);

            foreach ($request->items as $item) {
                // Verify scrap belongs to company
                $scrap = Scrap::where('company_id', $companyId)->findOrFail($item['scrapId']);

                PaymentItem::create([
                    'payment_id' => $payment->id,
                    'scrap_id' => $item['scrapId'],
                    'amount' => $item['amount'],
                    'quantity' => $item['quantity'],
                ]);
            }

            // Update client debt
            $this->updateClientDebt($client->id);

            DB::commit();

            return response()->json($this->formatPayment($payment->load(['client:id,business_name,origin', 'items.scrap:id,name,unit_measure'])), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    #[Put(
        "/payments/{id}",
        "Actualizar un pago existente",
        "Payments",
        true,
        new \OpenApi\Attributes\RequestBody(
            required: true,
            content: new \OpenApi\Attributes\JsonContent(
                properties: [
                    new \OpenApi\Attributes\Property(property: "date", type: "string", format: "date", nullable: true),
                    new \OpenApi\Attributes\Property(
                        property: "items",
                        type: "array",
                        items: new \OpenApi\Attributes\Items(
                            type: "object",
                            properties: [
                                new \OpenApi\Attributes\Property(property: "scrapId", type: "string"),
                                new \OpenApi\Attributes\Property(property: "amount", type: "number", format: "float"),
                                new \OpenApi\Attributes\Property(property: "quantity", type: "number", format: "float"),
                            ]
                        ),
                        nullable: true
                    ),
                    new \OpenApi\Attributes\Property(property: "notes", type: "string", nullable: true),
                ]
            )
        ),
        [],
        [
            new \OpenApi\Attributes\Parameter(name: "id", in: "path", required: true, schema: new \OpenApi\Attributes\Schema(type: "string"), description: "ID del pago"),
        ]
    )]
    public function update(Request $request, $id)
    {
        $companyId = $request->user()->company_id;
        $payment = Payment::where('company_id', $companyId)->findOrFail($id);

        $request->validate([
            'date' => 'sometimes|date',
            'items' => 'sometimes|array|min:1',
            'items.*.scrapId' => 'required|exists:scraps,id',
            'items.*.amount' => 'required|numeric|min:0',
            'items.*.quantity' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            if ($request->has('date')) {
                $payment->date = $request->date;
            }

            if ($request->has('items')) {
                $totalValue = collect($request->items)->sum('amount');
                $payment->total_value = $totalValue;

                // Delete old items
                $payment->items()->delete();

                // Create new items
                foreach ($request->items as $item) {
                    // Verify scrap belongs to company
                    $scrap = Scrap::where('company_id', $companyId)->findOrFail($item['scrapId']);

                    PaymentItem::create([
                        'payment_id' => $payment->id,
                        'scrap_id' => $item['scrapId'],
                        'amount' => $item['amount'],
                        'quantity' => $item['quantity'],
                    ]);
                }
            }

            if ($request->has('notes')) {
                $payment->notes = $request->notes;
            }

            $payment->save();

            // Update client debt
            $this->updateClientDebt($payment->client_id);

            DB::commit();

            return response()->json($this->formatPayment($payment->fresh()->load(['client:id,business_name,origin', 'items.scrap:id,name,unit_measure'])));
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    #[Get(
        "/clients/{clientId}/payments",
        "Obtener todos los pagos de un cliente específico",
        "Payments",
        true,
        [
            new \OpenApi\Attributes\Parameter(name: "clientId", in: "path", required: true, schema: new \OpenApi\Attributes\Schema(type: "string"), description: "ID del cliente"),
        ]
    )]
    public function clientPayments(Request $request, $clientId)
    {
        $companyId = $request->user()->company_id;
        $client = Client::where('company_id', $companyId)->findOrFail($clientId);

        $payments = Payment::where('client_id', $clientId)
            ->with(['client:id,business_name,origin', 'items.scrap:id,name,unit_measure'])
            ->get()
            ->map(function ($payment) {
                return $this->formatPayment($payment);
            });

        return response()->json([
            'payments' => $payments,
        ]);
    }

    private function formatPayment(Payment $payment): array
    {
        $data = [
            'id' => $payment->id,
            'companyId' => $payment->company_id,
            'clientId' => $payment->client_id,
            'clientName' => $payment->client->business_name,
            'clientOrigin' => $payment->client->origin,
            'date' => $payment->date->toISOString(),
            'items' => $payment->items->map(function ($item) {
                return [
                    'scrapId' => $item->scrap_id,
                    'scrapName' => $item->scrap->name,
                    'scrapUnitMeasure' => $item->scrap->unit_measure,
                    'amount' => (float) $item->amount,
                    'quantity' => (float) $item->quantity,
                ];
            }),
            'totalValue' => (float) $payment->total_value,
            'notes' => $payment->notes,
            'createdAt' => $payment->created_at->toISOString(),
        ];

        if ($payment->updated_at) {
            $data['updatedAt'] = $payment->updated_at->toISOString();
        }

        return $data;
    }

    private function updateClientDebt($clientId)
    {
        $client = Client::find($clientId);
        if ($client) {
            $totalCredit = \App\Models\Credit::where('client_id', $clientId)->sum('amount');
            $totalPaid = Payment::where('client_id', $clientId)->sum('total_value');
            $client->current_debt = $totalCredit - $totalPaid;
            $client->save();
        }
    }
}
