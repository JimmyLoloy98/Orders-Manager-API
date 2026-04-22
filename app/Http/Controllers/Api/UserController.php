<?php

namespace App\Http\Controllers\Api;

use App\Attributes\OpenApi\Post;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use OpenApi\Attributes as OA;

class UserController extends Controller
{
    #[Post("/users", "Crear un nuevo usuario", "Users", true, new OA\RequestBody(
        content: new OA\JsonContent(
            required: ["name", "username", "password", "role"],
            properties: [
                new OA\Property(property: "name", type: "string", example: "Juan Perez"),
                new OA\Property(property: "username", type: "string", example: "juanp"),
                new OA\Property(property: "password", type: "string", example: "password123"),
                new OA\Property(property: "role", type: "string", enum: ["mozo", "admin"], example: "mozo"),
                new OA\Property(property: "avatar", type: "string", example: "https://example.com/avatar.png")
            ]
        )
    ), responses: [
        new OA\Response(
            response: 201,
            description: "Usuario creado exitosamente",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "success", type: "boolean"),
                    new OA\Property(property: "message", type: "string"),
                    new OA\Property(property: "data", type: "object")
                ]
            )
        )
    ])]
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users',
            'password' => 'required|string|min:6',
            'role' => 'required|string|in:mozo,admin',
            'avatar' => 'nullable|string',
        ]);

        $user = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'avatar' => $request->avatar,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Usuario creado exitosamente',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'role' => $user->role,
                'avatar' => $user->avatar,
            ]
        ], 201);
    }
}
