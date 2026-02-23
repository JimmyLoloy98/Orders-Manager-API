<?php

namespace App\Http\Controllers\Api;

use App\Attributes\OpenApi\Get;
use App\Attributes\OpenApi\Patch;
use App\Attributes\OpenApi\Post;
use App\Attributes\OpenApi\Put;
use App\Helpers\OpenApiHelper;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Credit;
use App\Models\CreditItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CreditController extends Controller
{
    #[Get(
        "/credits",
        "Listar todos los créditos",
        "Credits",
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
        $query = Credit::where('company_id', $companyId)
            ->with(['client:id,business_name,origin', 'items']);

        // Filter by client
        if ($request->has('clientId')) {
            $query->where('client_id', $request->query('clientId'));
        }

        $page = $request->query('page', 1);
        $limit = $request->query('limit', 10);
        $total = $query->count();

        $credits = $query->skip(($page - 1) * $limit)
            ->take($limit)
            ->get()
            ->map(function ($credit) {
                return $this->formatCredit($credit);
            });

        return response()->json([
            'credits' => $credits,
            'total' => $total,
            'page' => (int) $page,
            'limit' => (int) $limit,
        ]);
    }

    #[Get(
        "/credits/{id}",
        "Obtener detalles de un crédito específico",
        "Credits",
        true,
        [
            new \OpenApi\Attributes\Parameter(name: "id", in: "path", required: true, schema: new \OpenApi\Attributes\Schema(type: "string"), description: "ID del crédito"),
        ]
    )]
    public function show(Request $request, $id)
    {
        $companyId = $request->user()->company_id;
        $credit = Credit::where('company_id', $companyId)
            ->with(['client:id,business_name,origin', 'items'])
            ->findOrFail($id);

        return response()->json($this->formatCredit($credit));
    }

    #[Post(
        "/credits",
        "Crear un nuevo crédito",
        "Credits",
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
                                new \OpenApi\Attributes\Property(property: "description", type: "string", example: "Material de construcción"),
                                new \OpenApi\Attributes\Property(property: "price", type: "number", format: "float", example: 1500.00),
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
            'items.*.description' => 'required|string',
            'items.*.price' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $companyId = $request->user()->company_id;
        $clientId = $request->input('clientId', $request->input('client_id'));
        $client = Client::where('company_id', $companyId)->findOrFail($clientId);

        DB::beginTransaction();
        try {
            $amount = collect($request->items)->sum('price');

            $credit = Credit::create([
                'company_id' => $companyId,
                'client_id' => $clientId,
                'date' => $request->date,
                'amount' => $amount,
                'notes' => $request->notes,
            ]);

            foreach ($request->items as $item) {
                CreditItem::create([
                    'credit_id' => $credit->id,
                    'description' => $item['description'],
                    'price' => $item['price'],
                ]);
            }

            // Update client debt
            $this->updateClientDebt($client->id);

            DB::commit();

            return response()->json($this->formatCredit($credit->load(['client:id,business_name,origin', 'items'])), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    #[Put(
        "/credits/{id}",
        "Actualizar un crédito existente",
        "Credits",
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
                                new \OpenApi\Attributes\Property(property: "description", type: "string"),
                                new \OpenApi\Attributes\Property(property: "price", type: "number", format: "float"),
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
            new \OpenApi\Attributes\Parameter(name: "id", in: "path", required: true, schema: new \OpenApi\Attributes\Schema(type: "string"), description: "ID del crédito"),
        ]
    )]
    public function update(Request $request, $id)
    {
        $companyId = $request->user()->company_id;
        $credit = Credit::where('company_id', $companyId)->findOrFail($id);

        $request->validate([
            'date' => 'sometimes|date',
            'items' => 'sometimes|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.price' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            if ($request->has('date')) {
                $credit->date = $request->date;
            }

            if ($request->has('items')) {
                $amount = collect($request->items)->sum('price');
                $credit->amount = $amount;

                // Delete old items
                $credit->items()->delete();

                // Create new items
                foreach ($request->items as $item) {
                    CreditItem::create([
                        'credit_id' => $credit->id,
                        'description' => $item['description'],
                        'price' => $item['price'],
                    ]);
                }
            }

            if ($request->has('notes')) {
                $credit->notes = $request->notes;
            }

            $credit->save();

            // Update client debt
            $this->updateClientDebt($credit->client_id);

            DB::commit();

            return response()->json($this->formatCredit($credit->fresh()->load(['client:id,business_name,origin', 'items'])));
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }


    #[Get(
        "/clients/{clientId}/credits",
        "Obtener todos los créditos de un cliente específico",
        "Credits",
        true,
        [
            new \OpenApi\Attributes\Parameter(name: "clientId", in: "path", required: true, schema: new \OpenApi\Attributes\Schema(type: "string"), description: "ID del cliente"),
        ]
    )]
    public function clientCredits(Request $request, $clientId)
    {
        $companyId = $request->user()->company_id;
        $client = Client::where('company_id', $companyId)->findOrFail($clientId);

        $credits = Credit::where('client_id', $clientId)
            ->with(['client:id,business_name,origin', 'items'])
            ->get()
            ->map(function ($credit) {
                return $this->formatCredit($credit);
            });

        return response()->json([
            'credits' => $credits,
        ]);
    }

    private function formatCredit(Credit $credit): array
    {
        $data = [
            'id' => $credit->id,
            'companyId' => $credit->company_id,
            'clientId' => $credit->client_id,
            'clientName' => $credit->client->business_name,
            'clientOrigin' => $credit->client->origin,
            'date' => $credit->date->toISOString(),
            'items' => $credit->items->map(function ($item) {
                return [
                    'description' => $item->description,
                    'price' => (float) $item->price,
                ];
            }),
            'amount' => (float) $credit->amount,
            'notes' => $credit->notes,
            'createdAt' => $credit->created_at->toISOString(),
        ];

        if ($credit->updated_at) {
            $data['updatedAt'] = $credit->updated_at->toISOString();
        }

        return $data;
    }

    private function updateClientDebt($clientId)
    {
        $client = Client::find($clientId);
        if ($client) {
            $totalCredit = Credit::where('client_id', $clientId)->sum('amount');
            $totalPaid = \App\Models\Payment::where('client_id', $clientId)->sum('total_value');
            $client->current_debt = $totalCredit - $totalPaid;
            $client->save();
        }
    }
}
