<?php

namespace App\Http\Controllers\Api;

use App\Attributes\OpenApi\Get;
use App\Attributes\OpenApi\Post;
use App\Attributes\OpenApi\Put;
use App\Http\Controllers\Controller;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('menu_items', $filename, 'public');
            $imagePath = asset('storage/' . $path);
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
        content: new OA\MediaType(
            mediaType: "multipart/form-data",
            schema: new OA\Schema(
                properties: [
                    new OA\Property(property: "name", type: "string"),
                    new OA\Property(property: "price", type: "number", format: "float"),
                    new OA\Property(property: "categoryId", type: "integer"),
                    new OA\Property(property: "description", type: "string"),
                    new OA\Property(property: "image", type: "string", format: "binary"),
                    new OA\Property(property: "_method", type: "string", default: "PUT"),
                ]
            )
        )
    ))]
    public function update(Request $request, int $id)
    {
        $item = MenuItem::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'price' => 'sometimes|numeric|min:0',
            'categoryId' => 'sometimes|exists:menu_categories,id',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        if ($request->has('name')) {
            $item->name = $request->name;
        }

        if ($request->has('price')) {
            $item->price = $request->price;
        }

        if ($request->has('categoryId')) {
            $item->menu_category_id = $request->categoryId;
        }

        if ($request->has('description')) {
            $item->description = $request->description;
        }

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('menu_items', $filename, 'public');
            $item->image = asset('storage/' . $path);
        }

        $item->save();

        return response()->json([
            'success' => true,
            'data' => $item->load('category')
        ]);
    }

    #[Get("/menu/categories", "Listar categorías del menú con sus ítems", "Menú", true)]
    public function categories(Request $request)
    {
        $categories = MenuCategory::with('items')->get();

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
