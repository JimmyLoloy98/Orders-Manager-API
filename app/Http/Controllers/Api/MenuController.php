<?php

namespace App\Http\Controllers\Api;

use App\Attributes\OpenApi\Get;
use App\Attributes\OpenApi\Post;
use App\Attributes\OpenApi\Put;
use App\Http\Controllers\Controller;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class MenuController extends Controller
{
    #[Get("/menu/items", "Listar items del menú", "Menú", true, [
        new OA\Parameter(name: "category", in: "query", required: false, schema: new OA\Schema(type: "string"), description: "Filtrar por nombre de categoría"),
        new OA\Parameter(name: "search", in: "query", required: false, schema: new OA\Schema(type: "string"), description: "Búsqueda por nombre"),
        new OA\Parameter(name: "available", in: "query", required: false, schema: new OA\Schema(type: "boolean"), description: "Filtrar por disponibilidad"),
    ])]
    public function index(Request $request)
    {
        $companyId = $request->user()->company_id;
        $category = $request->query('category');
        $search = $request->query('search');
        $available = $request->query('available');

        $query = MenuItem::where('company_id', $companyId)->with('category');

        if ($category) {
            $query->whereHas('category', function ($q) use ($category) {
                $q->where('name', 'like', "%$category%");
            });
        }

        if ($search) {
            $query->where('name', 'like', "%$search%");
        }

        if ($available !== null) {
            $query->where('available', filter_var($available, FILTER_VALIDATE_BOOLEAN));
        }

        return response()->json([
            'success' => true,
            'data' => $query->get()
        ]);
    }

    #[Post("/menu/items", "Crear item del menú", "Menú", true, new OA\RequestBody(
        content: new OA\MediaType(
            mediaType: "multipart/form-data",
            schema: new OA\Schema(
                required: ["name", "categoryId", "price"],
                properties: [
                    new OA\Property(property: "name", type: "string"),
                    new OA\Property(property: "categoryId", type: "integer"),
                    new OA\Property(property: "price", type: "number", format: "float"),
                    new OA\Property(property: "description", type: "string"),
                    new OA\Property(property: "image", type: "string", format: "binary"),
                    new OA\Property(property: "available", type: "boolean", default: true),
                ]
            )
        )
    ))]
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'categoryId' => 'required|exists:menu_categories,id',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
            'available' => 'nullable|boolean',
        ]);

        $companyId = $request->user()->company_id;

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('menu_items', 'public');
        }

        $item = MenuItem::create([
            'company_id' => $companyId,
            'menu_category_id' => $request->categoryId,
            'name' => $request->name,
            'description' => $request->description,
            'price' => $request->price,
            'image' => $imagePath,
            'available' => $request->available ?? true,
        ]);

        return response()->json([
            'success' => true,
            'data' => $item
        ], 201);
    }

    #[Put("/menu/items/{itemId}", "Actualizar item del menú", "Menú", true, new OA\RequestBody(
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "name", type: "string"),
                new OA\Property(property: "price", type: "number", format: "float"),
                new OA\Property(property: "available", type: "boolean"),
            ]
        )
    ))]
    public function update(Request $request, $id)
    {
        $companyId = $request->user()->company_id;
        $item = MenuItem::where('company_id', $companyId)->findOrFail($id);

        $item->update($request->only(['name', 'price', 'description', 'available']));

        return response()->json([
            'success' => true,
            'data' => $item
        ]);
    }

    #[Get("/menu/categories", "Listar categorías del menú", "Menú", true)]
    public function categories(Request $request)
    {
        $companyId = $request->user()->company_id;
        $categories = MenuCategory::where('company_id', $companyId)->get();

        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    #[Post("/menu/categories", "Crear categoría del menú", "Menú", true, new OA\RequestBody(
        content: new OA\JsonContent(
            required: ["name"],
            properties: [
                new OA\Property(property: "name", type: "string")
            ]
        )
    ))]
    public function storeCategory(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $companyId = $request->user()->company_id;

        $category = MenuCategory::create([
            'company_id' => $companyId,
            'name' => $request->name,
        ]);

        return response()->json([
            'success' => true,
            'data' => $category
        ], 201);
    }
}
