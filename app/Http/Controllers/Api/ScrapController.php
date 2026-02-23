<?php

namespace App\Http\Controllers\Api;

use App\Attributes\OpenApi\Delete;
use App\Attributes\OpenApi\Get;
use App\Attributes\OpenApi\Post;
use App\Attributes\OpenApi\Put;
use App\Helpers\OpenApiHelper;
use App\Http\Controllers\Controller;
use App\Models\Scrap;
use Illuminate\Http\Request;

class ScrapController extends Controller
{
    #[Get(
        "/scraps",
        "Listar todos los tipos de chatarra",
        "Scraps",
        true,
        [
            new \OpenApi\Attributes\Parameter(name: "page", in: "query", required: false, schema: new \OpenApi\Attributes\Schema(type: "integer", default: 1), description: "Número de página"),
            new \OpenApi\Attributes\Parameter(name: "limit", in: "query", required: false, schema: new \OpenApi\Attributes\Schema(type: "integer", default: 10), description: "Registros por página"),
            new \OpenApi\Attributes\Parameter(name: "unitMeasure", in: "query", required: false, schema: new \OpenApi\Attributes\Schema(type: "string", enum: ["kg", "und"]), description: "Filtrar por unidad de medida"),
        ]
    )]
    public function index(Request $request)
    {
        $companyId = $request->user()->company_id;
        $query = Scrap::where('company_id', $companyId);

        // Filter by unit measure
        if ($request->has('unitMeasure')) {
            $query->where('unit_measure', $request->query('unitMeasure'));
        }

        $page = $request->query('page', 1);
        $limit = $request->query('limit', 10);
        $total = $query->count();

        $scraps = $query->skip(($page - 1) * $limit)
            ->take($limit)
            ->get()
            ->map(function ($scrap) {
                return $this->formatScrap($scrap);
            });

        return response()->json([
            'scraps' => $scraps,
            'total' => $total,
            'page' => (int) $page,
            'limit' => (int) $limit,
        ]);
    }

    #[Get(
        "/scraps/{id}",
        "Obtener detalles de un tipo de chatarra específico",
        "Scraps",
        true,
        [
            new \OpenApi\Attributes\Parameter(name: "id", in: "path", required: true, schema: new \OpenApi\Attributes\Schema(type: "string"), description: "ID del tipo de chatarra"),
        ]
    )]
    public function show(Request $request, $id)
    {
        $companyId = $request->user()->company_id;
        $scrap = Scrap::where('company_id', $companyId)->findOrFail($id);

        return response()->json($this->formatScrap($scrap));
    }

    #[Post(
        "/scraps",
        "Crear un nuevo tipo de chatarra",
        "Scraps",
        true,
        new \OpenApi\Attributes\RequestBody(
            required: true,
            content: new \OpenApi\Attributes\JsonContent(
                required: ["name", "unitMeasure"],
                properties: [
                    new \OpenApi\Attributes\Property(property: "name", type: "string", example: "Cobre"),
                    new \OpenApi\Attributes\Property(property: "description", type: "string", nullable: true, example: "Chatarra de cobre"),
                    new \OpenApi\Attributes\Property(property: "unitMeasure", type: "string", enum: ["kg", "und"], example: "kg"),
                ]
            )
        )
    )]
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'description' => 'nullable|string',
            'unitMeasure' => 'required_without:unit_measure|string|in:kg,und',
            'unit_measure' => 'sometimes|string|in:kg,und',
        ]);

        $unitMeasure = $request->input('unitMeasure', $request->input('unit_measure'));

        $scrap = Scrap::create([
            'company_id' => $request->user()->company_id,
            'name' => $request->name,
            'description' => $request->description,
            'unit_measure' => $unitMeasure,
        ]);

        return response()->json($this->formatScrap($scrap), 201);
    }

    #[Put(
        "/scraps/{id}",
        "Actualizar un tipo de chatarra existente",
        "Scraps",
        true,
        new \OpenApi\Attributes\RequestBody(
            required: true,
            content: new \OpenApi\Attributes\JsonContent(
                properties: [
                    new \OpenApi\Attributes\Property(property: "name", type: "string", nullable: true),
                    new \OpenApi\Attributes\Property(property: "description", type: "string", nullable: true),
                    new \OpenApi\Attributes\Property(property: "unitMeasure", type: "string", enum: ["kg", "und"], nullable: true),
                ]
            )
        ),
        [],
        [
            new \OpenApi\Attributes\Parameter(name: "id", in: "path", required: true, schema: new \OpenApi\Attributes\Schema(type: "string"), description: "ID del tipo de chatarra"),
        ]
    )]
    public function update(Request $request, $id)
    {
        $companyId = $request->user()->company_id;
        $scrap = Scrap::where('company_id', $companyId)->findOrFail($id);

        $request->validate([
            'name' => 'sometimes|string',
            'description' => 'nullable|string',
            'unitMeasure' => 'sometimes|string|in:kg,und',
            'unit_measure' => 'sometimes|string|in:kg,und',
        ]);

        $data = $request->only(['name', 'description']);

        if ($request->has('unitMeasure')) {
            $data['unit_measure'] = $request->unitMeasure;
        } elseif ($request->has('unit_measure')) {
            $data['unit_measure'] = $request->unit_measure;
        }

        $scrap->update($data);

        return response()->json($this->formatScrap($scrap->fresh()));
    }

    #[Delete(
        "/scraps/{id}",
        "Eliminar un tipo de chatarra",
        "Scraps",
        true,
        [],
        [
            new \OpenApi\Attributes\Parameter(name: "id", in: "path", required: true, schema: new \OpenApi\Attributes\Schema(type: "string"), description: "ID del tipo de chatarra"),
        ]
    )]
    public function destroy(Request $request, $id)
    {
        $companyId = $request->user()->company_id;
        $scrap = Scrap::where('company_id', $companyId)->findOrFail($id);
        $scrap->delete();

        return response()->json([
            'message' => 'Scrap type deleted successfully',
        ]);
    }

    private function formatScrap(Scrap $scrap): array
    {
        $data = [
            'id' => $scrap->id,
            'companyId' => $scrap->company_id,
            'name' => $scrap->name,
            'description' => $scrap->description,
            'unitMeasure' => $scrap->unit_measure,
            'createdAt' => $scrap->created_at->toISOString(),
        ];

        if ($scrap->updated_at) {
            $data['updatedAt'] = $scrap->updated_at->toISOString();
        }

        return $data;
    }
}
