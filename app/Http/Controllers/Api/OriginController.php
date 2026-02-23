<?php

namespace App\Http\Controllers\Api;

use App\Attributes\OpenApi\Delete;
use App\Attributes\OpenApi\Get;
use App\Attributes\OpenApi\Post;
use App\Attributes\OpenApi\Put;
use App\Helpers\OpenApiHelper;
use App\Http\Controllers\Controller;
use App\Models\Origin;
use Illuminate\Http\Request;

class OriginController extends Controller
{
    #[Get(
        "/origins",
        "Listar todos los lugares de procedencia",
        "Origins",
        true,
        [
            new \OpenApi\Attributes\Parameter(name: "page", in: "query", required: false, schema: new \OpenApi\Attributes\Schema(type: "integer", default: 1), description: "Número de página"),
            new \OpenApi\Attributes\Parameter(name: "limit", in: "query", required: false, schema: new \OpenApi\Attributes\Schema(type: "integer", default: 10), description: "Registros por página"),
        ]
    )]
    public function index(Request $request)
    {
        $companyId = $request->user()->company_id;
        $query = Origin::where('company_id', $companyId);

        $page = $request->query('page', 1);
        $limit = $request->query('limit', 10);
        $total = $query->count();

        $origins = $query->skip(($page - 1) * $limit)
            ->take($limit)
            ->get()
            ->map(function ($origin) {
                return $this->formatOrigin($origin);
            });

        return response()->json([
            'origins' => $origins,
            'total' => $total,
            'page' => (int) $page,
            'limit' => (int) $limit,
        ]);
    }

    #[Get(
        "/origins/{id}",
        "Obtener detalles de un lugar de procedencia específico",
        "Origins",
        true,
        [
            new \OpenApi\Attributes\Parameter(name: "id", in: "path", required: true, schema: new \OpenApi\Attributes\Schema(type: "string"), description: "ID del lugar de procedencia"),
        ]
    )]
    public function show(Request $request, $id)
    {
        $companyId = $request->user()->company_id;
        $origin = Origin::where('company_id', $companyId)->findOrFail($id);

        return response()->json($this->formatOrigin($origin));
    }

    #[Post(
        "/origins",
        "Crear un nuevo lugar de procedencia",
        "Origins",
        true,
        new \OpenApi\Attributes\RequestBody(
            required: true,
            content: new \OpenApi\Attributes\JsonContent(
                required: ["name"],
                properties: [
                    new \OpenApi\Attributes\Property(property: "name", type: "string", example: "Lima"),
                    new \OpenApi\Attributes\Property(property: "description", type: "string", nullable: true, example: "Ciudad de Lima"),
                ]
            )
        )
    )]
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'description' => 'nullable|string',
        ]);

        $origin = Origin::create([
            'company_id' => $request->user()->company_id,
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return response()->json($this->formatOrigin($origin), 201);
    }

    #[Put(
        "/origins/{id}",
        "Actualizar un lugar de procedencia existente",
        "Origins",
        true,
        new \OpenApi\Attributes\RequestBody(
            required: true,
            content: new \OpenApi\Attributes\JsonContent(
                properties: [
                    new \OpenApi\Attributes\Property(property: "name", type: "string", nullable: true),
                    new \OpenApi\Attributes\Property(property: "description", type: "string", nullable: true),
                ]
            )
        ),
        [],
        [
            new \OpenApi\Attributes\Parameter(name: "id", in: "path", required: true, schema: new \OpenApi\Attributes\Schema(type: "string"), description: "ID del lugar de procedencia"),
        ]
    )]
    public function update(Request $request, $id)
    {
        $companyId = $request->user()->company_id;
        $origin = Origin::where('company_id', $companyId)->findOrFail($id);

        $request->validate([
            'name' => 'sometimes|string',
            'description' => 'nullable|string',
        ]);

        $origin->update($request->only(['name', 'description']));

        return response()->json($this->formatOrigin($origin->fresh()));
    }

    #[Delete(
        "/origins/{id}",
        "Eliminar un lugar de procedencia",
        "Origins",
        true,
        [],
        [
            new \OpenApi\Attributes\Parameter(name: "id", in: "path", required: true, schema: new \OpenApi\Attributes\Schema(type: "string"), description: "ID del lugar de procedencia"),
        ]
    )]
    public function destroy(Request $request, $id)
    {
        $companyId = $request->user()->company_id;
        $origin = Origin::where('company_id', $companyId)->findOrFail($id);
        $origin->delete();

        return response()->json([
            'message' => 'Origin deleted successfully',
        ]);
    }

    private function formatOrigin(Origin $origin): array
    {
        $data = [
            'id' => $origin->id,
            'companyId' => $origin->company_id,
            'name' => $origin->name,
            'description' => $origin->description,
            'createdAt' => $origin->created_at->toISOString(),
        ];

        if ($origin->updated_at) {
            $data['updatedAt'] = $origin->updated_at->toISOString();
        }

        return $data;
    }
}
