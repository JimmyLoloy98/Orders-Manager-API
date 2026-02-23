<?php

namespace App\Http\Controllers\Api;

use App\Attributes\OpenApi\Delete;
use App\Attributes\OpenApi\Get;
use App\Attributes\OpenApi\Post;
use App\Attributes\OpenApi\Put;
use App\Helpers\OpenApiHelper;
use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ClientController extends Controller
{
    #[Get(
        "/clients",
        "Listar todos los clientes",
        "Clients",
        true,
        [
            new \OpenApi\Attributes\Parameter(name: "page", in: "query", required: false, schema: new \OpenApi\Attributes\Schema(type: "integer", default: 1), description: "Número de página"),
            new \OpenApi\Attributes\Parameter(name: "limit", in: "query", required: false, schema: new \OpenApi\Attributes\Schema(type: "integer", default: 10), description: "Registros por página"),
            new \OpenApi\Attributes\Parameter(name: "origin", in: "query", required: false, schema: new \OpenApi\Attributes\Schema(type: "string"), description: "Filtrar por lugar de procedencia"),
        ]
    )]
    public function index(Request $request)
    {
        $companyId = $request->user()->company_id;
        $query = Client::where('company_id', $companyId);

        // Filter by origin
        if ($request->has('origin')) {
            $query->where('origin', $request->query('origin'));
        }

        $page = $request->query('page', 1);
        $limit = $request->query('limit', 10);
        $total = $query->count();

        $clients = $query->skip(($page - 1) * $limit)
            ->take($limit)
            ->get()
            ->map(function ($client) {
                return $this->formatClient($client);
            });

        return response()->json([
            'clients' => $clients,
            'total' => $total,
            'page' => (int) $page,
            'limit' => (int) $limit,
        ]);
    }

    #[Get(
        "/clients/{id}",
        "Obtener detalles de un cliente específico",
        "Clients",
        true,
        [
            new \OpenApi\Attributes\Parameter(name: "id", in: "path", required: true, schema: new \OpenApi\Attributes\Schema(type: "string"), description: "ID del cliente"),
        ]
    )]
    public function show(Request $request, $id)
    {
        $companyId = $request->user()->company_id;
        $client = Client::where('company_id', $companyId)->findOrFail($id);

        return response()->json($this->formatClient($client));
    }

    private function formatClient(Client $client): array
    {
        $data = [
            'id' => $client->id,
            'companyId' => $client->company_id,
            'username' => $client->username,
            'currentDebt' => (float) $client->current_debt,
            'createdAt' => $client->created_at->toISOString(),
        ];

        if ($client->updated_at) {
            $data['updatedAt'] = $client->updated_at->toISOString();
        }

        return $data;
    }
}
