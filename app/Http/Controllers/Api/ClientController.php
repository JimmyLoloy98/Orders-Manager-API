<?php

namespace App\Http\Controllers\Api;

use App\Attributes\OpenApi\Delete;
use App\Attributes\OpenApi\Get;
use App\Attributes\OpenApi\Post;
use App\Attributes\OpenApi\Put;
use App\Helpers\OpenApiHelper;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Credit;
use App\Models\Payment;
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

    #[Post(
        "/clients",
        "Crear un nuevo cliente",
        "Clients",
        true,
        new \OpenApi\Attributes\RequestBody(
            required: true,
            content: new \OpenApi\Attributes\JsonContent(
                required: ["business_name", "owner_name"],
                properties: [
                    new \OpenApi\Attributes\Property(property: "business_name", type: "string", example: "Taller Mecánico Juan"),
                    new \OpenApi\Attributes\Property(property: "owner_name", type: "string", example: "Juan Pérez"),
                    new \OpenApi\Attributes\Property(property: "dni", type: "string", nullable: true),
                    new \OpenApi\Attributes\Property(property: "ruc", type: "string", nullable: true),
                    new \OpenApi\Attributes\Property(property: "phone", type: "string", nullable: true),
                    new \OpenApi\Attributes\Property(property: "email", type: "string", format: "email", nullable: true),
                    new \OpenApi\Attributes\Property(property: "address", type: "string", nullable: true),
                    new \OpenApi\Attributes\Property(property: "origin", type: "string", nullable: true),
                    new \OpenApi\Attributes\Property(property: "notes", type: "string", nullable: true),
                    new \OpenApi\Attributes\Property(property: "photo", type: "string", nullable: true),
                ]
            )
        )
    )]
    public function store(Request $request)
    {
        $request->validate([
            'business_name' => 'required|string',
            'owner_name' => 'required|string',
            'dni' => 'nullable|string',
            'ruc' => 'nullable|string',
            'phone' => 'nullable|string',
            'email' => 'nullable|email',
            'address' => 'nullable|string',
            'origin' => 'nullable|string',
            'notes' => 'nullable|string',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // Max 5MB
        ]);

        $photoPath = null;

        // Handle image upload
        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('clients', $filename, 'public');
            $photoPath = Storage::url($path);
        }

        $client = Client::create([
            'company_id' => $request->user()->company_id,
            'business_name' => $request->input('businessName', $request->input('business_name')),
            'owner_name' => $request->input('ownerName', $request->input('owner_name')),
            'dni' => $request->dni,
            'ruc' => $request->ruc,
            'phone' => $request->phone,
            'email' => $request->email,
            'address' => $request->address,
            'origin' => $request->origin,
            'notes' => $request->notes,
            'photo' => $photoPath,
            'current_debt' => 0,
        ]);

        return response()->json($this->formatClient($client), 201);
    }

    #[Put(
        "/clients/{id}",
        "Actualizar un cliente existente",
        "Clients",
        true,
        new \OpenApi\Attributes\RequestBody(
            required: true,
            content: new \OpenApi\Attributes\JsonContent(
                properties: [
                    new \OpenApi\Attributes\Property(property: "business_name", type: "string", nullable: true),
                    new \OpenApi\Attributes\Property(property: "owner_name", type: "string", nullable: true),
                    new \OpenApi\Attributes\Property(property: "dni", type: "string", nullable: true),
                    new \OpenApi\Attributes\Property(property: "ruc", type: "string", nullable: true),
                    new \OpenApi\Attributes\Property(property: "phone", type: "string", nullable: true),
                    new \OpenApi\Attributes\Property(property: "email", type: "string", format: "email", nullable: true),
                    new \OpenApi\Attributes\Property(property: "address", type: "string", nullable: true),
                    new \OpenApi\Attributes\Property(property: "origin", type: "string", nullable: true),
                    new \OpenApi\Attributes\Property(property: "notes", type: "string", nullable: true),
                    new \OpenApi\Attributes\Property(property: "photo", type: "string", nullable: true),
                ]
            )
        ),
        [],
        [
            new \OpenApi\Attributes\Parameter(name: "id", in: "path", required: true, schema: new \OpenApi\Attributes\Schema(type: "string"), description: "ID del cliente"),
        ]
    )]
    public function update(Request $request, $id)
    {
        $companyId = $request->user()->company_id;
        $client = Client::where('company_id', $companyId)->findOrFail($id);

        $request->validate([
            'businessName' => 'sometimes|string',
            'business_name' => 'sometimes|string',
            'ownerName' => 'sometimes|string',
            'owner_name' => 'sometimes|string',
            'dni' => 'nullable|string',
            'ruc' => 'nullable|string',
            'phone' => 'nullable|string',
            'email' => 'nullable|email',
            'address' => 'nullable|string',
            'origin' => 'nullable|string',
            'notes' => 'nullable|string',
            'photo' => 'nullable', // Allow existing URL or new File
        ]);

        $data = $request->only([
            'dni',
            'ruc',
            'phone',
            'email',
            'address',
            'origin',
            'notes',
        ]);

        if ($request->has('businessName')) $data['business_name'] = $request->businessName;
        if ($request->has('business_name')) $data['business_name'] = $request->business_name;
        if ($request->has('ownerName')) $data['owner_name'] = $request->ownerName;
        if ($request->has('owner_name')) $data['owner_name'] = $request->owner_name;

        // Handle image upload or removal
        if ($request->hasFile('photo')) {
            // Delete old photo if exists
            if ($client->photo) {
                $oldPath = str_replace('/storage/', '', parse_url($client->photo, PHP_URL_PATH));
                Storage::disk('public')->delete($oldPath);
            }

            $file = $request->file('photo');
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('clients', $filename, 'public');
            $data['photo'] = Storage::url($path);
        }

        $client->update($data);

        return response()->json($this->formatClient($client->fresh()));
    }

    #[Delete(
        "/clients/{id}",
        "Eliminar un cliente",
        "Clients",
        true,
        [],
        [
            new \OpenApi\Attributes\Parameter(name: "id", in: "path", required: true, schema: new \OpenApi\Attributes\Schema(type: "string"), description: "ID del cliente"),
        ]
    )]
    public function destroy(Request $request, $id)
    {
        $companyId = $request->user()->company_id;
        $client = Client::where('company_id', $companyId)->findOrFail($id);

        // Delete associated photo if exists
        if ($client->photo) {
            $oldPath = str_replace('/storage/', '', parse_url($client->photo, PHP_URL_PATH));
            Storage::disk('public')->delete($oldPath);
        }

        $client->delete();

        return response()->json([
            'message' => 'Client deleted successfully',
        ]);
    }

    #[Get(
        "/clients/{id}/summary",
        "Obtener resumen financiero de un cliente",
        "Clients",
        true,
        [
            new \OpenApi\Attributes\Parameter(name: "id", in: "path", required: true, schema: new \OpenApi\Attributes\Schema(type: "string"), description: "ID del cliente"),
        ]
    )]
    public function summary(Request $request, $id)
    {
        $companyId = $request->user()->company_id;
        $client = Client::where('company_id', $companyId)->findOrFail($id);

        $totalCredit = Credit::where('client_id', $client->id)->sum('amount');
        $totalPaid = Payment::where('client_id', $client->id)->sum('total_value');
        $pendingDebt = $totalCredit - $totalPaid;
        $creditsCount = Credit::where('client_id', $client->id)->count();
        $paymentsCount = Payment::where('client_id', $client->id)->count();

        return response()->json([
            'totalCredit' => (float) $totalCredit,
            'totalPaid' => (float) $totalPaid,
            'pendingDebt' => (float) $pendingDebt,
            'creditsCount' => $creditsCount,
            'paymentsCount' => $paymentsCount,
        ]);
    }

    #[Post(
        "/clients/{id}/upload-photo",
        "Subir foto de un cliente",
        "Clients",
        true,
        new \OpenApi\Attributes\RequestBody(
            required: true,
            content: new \OpenApi\Attributes\MediaType(
                mediaType: "multipart/form-data",
                schema: new \OpenApi\Attributes\Schema(
                    required: ["photo"],
                    properties: [
                        new \OpenApi\Attributes\Property(
                            property: "photo",
                            type: "string",
                            format: "binary",
                            description: "Imagen del cliente (JPEG, PNG, JPG, GIF, WEBP - Max 5MB)"
                        ),
                    ]
                )
            )
        ),
        [],
        [
            new \OpenApi\Attributes\Parameter(name: "id", in: "path", required: true, schema: new \OpenApi\Attributes\Schema(type: "string"), description: "ID del cliente"),
        ]
    )]
    public function uploadPhoto(Request $request, $id)
    {
        $companyId = $request->user()->company_id;
        $client = Client::where('company_id', $companyId)->findOrFail($id);

        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // Max 5MB
        ]);

        // Delete old photo if exists
        if ($client->photo) {
            $oldPath = str_replace('/storage/', '', parse_url($client->photo, PHP_URL_PATH));
            Storage::disk('public')->delete($oldPath);
        }

        // Store new photo
        $file = $request->file('photo');
        $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('clients', $filename, 'public');
        $photoPath = Storage::url($path);

        // Update client
        $client->update(['photo' => $photoPath]);

        return response()->json([
            'message' => 'Photo uploaded successfully',
            'photo' => $photoPath,
            'client' => $this->formatClient($client->fresh()),
        ]);
    }

    #[Delete(
        "/clients/{id}/photo",
        "Eliminar foto de un cliente",
        "Clients",
        true,
        [],
        [
            new \OpenApi\Attributes\Parameter(name: "id", in: "path", required: true, schema: new \OpenApi\Attributes\Schema(type: "string"), description: "ID del cliente"),
        ]
    )]
    public function deletePhoto(Request $request, $id)
    {
        $companyId = $request->user()->company_id;
        $client = Client::where('company_id', $companyId)->findOrFail($id);

        if ($client->photo) {
            $oldPath = str_replace('/storage/', '', parse_url($client->photo, PHP_URL_PATH));
            Storage::disk('public')->delete($oldPath);

            $client->update(['photo' => null]);

            return response()->json([
                'message' => 'Photo deleted successfully',
                'client' => $this->formatClient($client->fresh()),
            ]);
        }

        return response()->json([
            'message' => 'No photo to delete',
        ], 404);
    }

    private function formatClient(Client $client): array
    {
        $data = [
            'id' => $client->id,
            'companyId' => $client->company_id,
            'businessName' => $client->business_name,
            'ownerName' => $client->owner_name,
            'dni' => $client->dni,
            'ruc' => $client->ruc,
            'photo' => $client->photo,
            'phone' => $client->phone,
            'email' => $client->email,
            'address' => $client->address,
            'origin' => $client->origin,
            'notes' => $client->notes,
            'currentDebt' => (float) $client->current_debt,
            'createdAt' => $client->created_at->toISOString(),
        ];

        if ($client->updated_at) {
            $data['updatedAt'] = $client->updated_at->toISOString();
        }

        return $data;
    }
}
