<?php

namespace App\Http\Controllers\Api;

use App\Attributes\OpenApi\Post;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;

class AuthController extends Controller
{
    #[Post("/auth/login", "Iniciar sesión en la aplicación", "Authentication", false, new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ["username", "password"],
            properties: [
                new OA\Property(property: "username", type: "string", format: "username", example: "username"),
                new OA\Property(property: "password", type: "string", format: "password", example: "password"),
            ]
        )
    ), responses: [
        new OA\Response(
            response: 200,
            description: "Login exitoso",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "user", properties: [
                        new OA\Property(property: "id", type: "integer"),
                        new OA\Property(property: "username", type: "string"),
                        new OA\Property(property: "name", type: "string"),
                        new OA\Property(property: "role", type: "string"),
                        new OA\Property(property: "avatar", type: "string")
                    ], type: "object"),
                    new OA\Property(property: "token", type: "string")
                ]
            )
        )
    ])]
    public function login(Request $request)
    {
        try {
            $request->validate([
                'username' => 'required|string',
                'password' => 'required',
            ]);

            $user = User::where('username', $request->username)
                ->first();

            if (! $user || ! Hash::check($request->password, $user->password)) {
                throw ValidationException::withMessages([
                    'username' => ['Las credenciales proporcionadas son incorrectas.'],
                ]);
            }

            $token = $user->createToken('auth-token')->plainTextToken;

            return response()->json([
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'name' => $user->name,
                    'role' => $user->role,
                    'avatar' => $user->avatar,
                ],
                'token' => $token,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Login error: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            if ($e instanceof ValidationException) {
                throw $e;
            }

            return response()->json([
                'message' => 'Error al iniciar sesión. Por favor, intenta de nuevo.',
                'error' => app()->environment('local') ? $e->getMessage() : null,
            ], 500);
        }
    }

    #[Post("/auth/logout", "Cerrar sesión", "Authentication")]
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }
}
