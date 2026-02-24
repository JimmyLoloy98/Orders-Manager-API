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
    ])]
    public function index(Request $request)
    {
        $category = $request->query('category');
        $search = $request->query('search');

        $query = MenuItem::with('category');

        if ($category) {
            $query->whereHas('category', function ($q) use ($category) {
                $q->where('name', 'like', "%$category%");
            });
        }

        if ($search) {
            $query->where('name', 'like', "%$search%");
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
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('menu_items', 'public');
        }

        $item = MenuItem::create([
            'menu_category_id' => $request->categoryId,
            'name' => $request->name,
            'description' => $request->description,
            'price' => $request->price,
            'image' => $imagePath,
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
            ]
        )
    ))]
    public function update(Request $request, $id)
    {
        $item = MenuItem::findOrFail($id);


        return response()->json([
            'success' => true,
            'data' => $item
        ]);
    }

    #[Get("/menu/categories", "Listar categorías del menú", "Menú", true)]
    public function categories(Request $request)
    {
        $categories = MenuCategory::all();

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

        $category = MenuCategory::create([
            'name' => $request->name,
        ]);

        return response()->json([
            'success' => true,
            'data' => $category
        ], 201);
    }
}
