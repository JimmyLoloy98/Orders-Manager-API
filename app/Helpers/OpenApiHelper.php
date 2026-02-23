<?php

namespace App\Helpers;

use OpenApi\Attributes as OA;

/**
 * Helper class for generating OpenAPI annotations
 * Follows SOLID principles:
 * - Single Responsibility: Only handles OpenAPI annotation creation
 * - Open/Closed: Can be extended without modifying existing code
 * - Dependency Inversion: Uses OpenAPI interfaces
 */
class OpenApiHelper
{
    /**
     * Create a GET endpoint annotation
     */
    public static function get(
        string $path,
        string $summary,
        string $tag,
        bool $requiresAuth = true,
        array $parameters = [],
        array $responses = []
    ): OA\Get {
        $defaultResponses = [
            new OA\Response(response: 200, description: "Operación exitosa"),
        ];

        if ($requiresAuth) {
            $defaultResponses[] = new OA\Response(response: 401, description: "No autenticado");
        }

        $responses = array_merge($defaultResponses, $responses);

        return new OA\Get(
            path: $path,
            summary: $summary,
            tags: [$tag],
            security: $requiresAuth ? [["bearerAuth" => []]] : [],
            parameters: $parameters,
            responses: $responses
        );
    }

    /**
     * Create a POST endpoint annotation
     */
    public static function post(
        string $path,
        string $summary,
        string $tag,
        bool $requiresAuth = true,
        ?OA\RequestBody $requestBody = null,
        array $responses = []
    ): OA\Post {
        $defaultResponses = [
            new OA\Response(response: 201, description: "Recurso creado exitosamente"),
        ];

        if ($requiresAuth) {
            $defaultResponses[] = new OA\Response(response: 401, description: "No autenticado");
        }

        $defaultResponses[] = new OA\Response(response: 422, description: "Error de validación");

        $responses = array_merge($defaultResponses, $responses);

        return new OA\Post(
            path: $path,
            summary: $summary,
            tags: [$tag],
            security: $requiresAuth ? [["bearerAuth" => []]] : [],
            requestBody: $requestBody,
            responses: $responses
        );
    }

    /**
     * Create a PUT endpoint annotation
     */
    public static function put(
        string $path,
        string $summary,
        string $tag,
        bool $requiresAuth = true,
        ?OA\RequestBody $requestBody = null,
        array $responses = []
    ): OA\Put {
        $defaultResponses = [
            new OA\Response(response: 200, description: "Recurso actualizado exitosamente"),
            new OA\Response(response: 404, description: "Recurso no encontrado"),
        ];

        if ($requiresAuth) {
            $defaultResponses[] = new OA\Response(response: 401, description: "No autenticado");
        }

        $responses = array_merge($defaultResponses, $responses);

        return new OA\Put(
            path: $path,
            summary: $summary,
            tags: [$tag],
            security: $requiresAuth ? [["bearerAuth" => []]] : [],
            requestBody: $requestBody,
            responses: $responses
        );
    }

    /**
     * Create a PATCH endpoint annotation
     */
    public static function patch(
        string $path,
        string $summary,
        string $tag,
        bool $requiresAuth = true,
        ?OA\RequestBody $requestBody = null,
        array $responses = []
    ): OA\Patch {
        $defaultResponses = [
            new OA\Response(response: 200, description: "Recurso actualizado exitosamente"),
            new OA\Response(response: 404, description: "Recurso no encontrado"),
        ];

        if ($requiresAuth) {
            $defaultResponses[] = new OA\Response(response: 401, description: "No autenticado");
        }

        $responses = array_merge($defaultResponses, $responses);

        return new OA\Patch(
            path: $path,
            summary: $summary,
            tags: [$tag],
            security: $requiresAuth ? [["bearerAuth" => []]] : [],
            requestBody: $requestBody,
            responses: $responses
        );
    }

    /**
     * Create a DELETE endpoint annotation
     */
    public static function delete(
        string $path,
        string $summary,
        string $tag,
        bool $requiresAuth = true,
        array $responses = []
    ): OA\Delete {
        $defaultResponses = [
            new OA\Response(response: 200, description: "Recurso eliminado exitosamente"),
            new OA\Response(response: 404, description: "Recurso no encontrado"),
        ];

        if ($requiresAuth) {
            $defaultResponses[] = new OA\Response(response: 401, description: "No autenticado");
        }

        $responses = array_merge($defaultResponses, $responses);

        return new OA\Delete(
            path: $path,
            summary: $summary,
            tags: [$tag],
            security: $requiresAuth ? [["bearerAuth" => []]] : [],
            responses: $responses
        );
    }

    /**
     * Create a query parameter annotation
     */
    public static function queryParam(
        string $name,
        string $type = "string",
        bool $required = false,
        mixed $default = null,
        string $description = ""
    ): OA\Parameter {
        $schema = new OA\Schema(type: $type);
        if ($default !== null) {
            $schema = new OA\Schema(type: $type, default: $default);
        }

        return new OA\Parameter(
            name: $name,
            in: "query",
            required: $required,
            description: $description,
            schema: $schema
        );
    }

    /**
     * Create a path parameter annotation
     */
    public static function pathParam(
        string $name,
        string $type = "string",
        string $description = ""
    ): OA\Parameter {
        return new OA\Parameter(
            name: $name,
            in: "path",
            required: true,
            description: $description,
            schema: new OA\Schema(type: $type)
        );
    }

    /**
     * Create request body for Scrap creation/update
     */
    public static function scrapRequestBody(): OA\RequestBody
    {
        return new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["name"],
                properties: [
                    new OA\Property(property: "name", type: "string", example: "Cobre"),
                    new OA\Property(property: "description", type: "string", nullable: true, example: "Chatarra de cobre"),
                ]
            )
        );
    }

    /**
     * Create request body for Client creation/update
     */
    public static function clientRequestBody(): OA\RequestBody
    {
        return new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["name"],
                properties: [
                    new OA\Property(property: "name", type: "string", example: "Juan Pérez"),
                    new OA\Property(property: "business_name", type: "string", nullable: true),
                    new OA\Property(property: "owner_name", type: "string", nullable: true),
                    new OA\Property(property: "dni", type: "string", nullable: true),
                    new OA\Property(property: "ruc", type: "string", nullable: true),
                    new OA\Property(property: "phone", type: "string", nullable: true),
                    new OA\Property(property: "email", type: "string", format: "email", nullable: true),
                    new OA\Property(property: "address", type: "string", nullable: true),
                    new OA\Property(property: "origin", type: "string", nullable: true),
                    new OA\Property(property: "notes", type: "string", nullable: true),
                    new OA\Property(property: "photo_url", type: "string", nullable: true),
                ]
            )
        );
    }

    /**
     * Create request body for Credit creation/update
     */
    public static function creditRequestBody(): OA\RequestBody
    {
        return new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["clientId", "date", "items"],
                properties: [
                    new OA\Property(property: "clientId", type: "string", example: "1"),
                    new OA\Property(property: "date", type: "string", format: "date", example: "2024-01-24"),
                    new OA\Property(
                        property: "items",
                        type: "array",
                        items: new OA\Items(
                            type: "object",
                            properties: [
                                new OA\Property(property: "description", type: "string", example: "Material de construcción"),
                                new OA\Property(property: "price", type: "number", format: "float", example: 1500.00),
                            ]
                        )
                    ),
                    new OA\Property(property: "notes", type: "string", nullable: true),
                ]
            )
        );
    }

    /**
     * Create request body for Payment creation/update
     */
    public static function paymentRequestBody(): OA\RequestBody
    {
        return new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["clientId", "date", "items"],
                properties: [
                    new OA\Property(property: "clientId", type: "string", example: "1"),
                    new OA\Property(property: "date", type: "string", format: "date", example: "2024-01-24"),
                    new OA\Property(
                        property: "items",
                        type: "array",
                        items: new OA\Items(
                            type: "object",
                            properties: [
                                new OA\Property(property: "scrapId", type: "string", example: "1"),
                                new OA\Property(property: "amount", type: "number", format: "float", example: 500.00),
                            ]
                        )
                    ),
                    new OA\Property(property: "notes", type: "string", nullable: true),
                ]
            )
        );
    }

    /**
     * Create request body for Origin creation/update
     */
    public static function originRequestBody(): OA\RequestBody
    {
        return new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["name"],
                properties: [
                    new OA\Property(property: "name", type: "string", example: "Lima"),
                    new OA\Property(property: "description", type: "string", nullable: true, example: "Ciudad de Lima"),
                ]
            )
        );
    }

    /**
     * Common query parameters for list endpoints
     */
    public static function listQueryParams(): array
    {
        return [
            new OA\Parameter(
                name: "page",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "integer", default: 1),
                description: "Número de página"
            ),
            new OA\Parameter(
                name: "limit",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "integer", default: 10),
                description: "Registros por página"
            ),
        ];
    }

    /**
     * Query parameters for clients list endpoint
     */
    public static function clientsListParams(): array
    {
        return [
            new OA\Parameter(
                name: "page",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "integer", default: 1),
                description: "Número de página"
            ),
            new OA\Parameter(
                name: "limit",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "integer", default: 10),
                description: "Registros por página"
            ),
            new OA\Parameter(
                name: "search",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "string"),
                description: "Búsqueda por nombre o descripción"
            ),
            new OA\Parameter(
                name: "origin",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "string"),
                description: "Filtrar por lugar de procedencia"
            ),
        ];
    }

    /**
     * Query parameters for credits list endpoint
     */
    public static function creditsListParams(): array
    {
        return [
            new OA\Parameter(
                name: "page",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "integer", default: 1),
                description: "Número de página"
            ),
            new OA\Parameter(
                name: "limit",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "integer", default: 10),
                description: "Registros por página"
            ),
            new OA\Parameter(
                name: "clientId",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "string"),
                description: "Filtrar por cliente"
            ),
            new OA\Parameter(
                name: "status",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "string", enum: ["pending", "partial", "paid"]),
                description: "Filtrar por estado"
            ),
            new OA\Parameter(
                name: "origin",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "string"),
                description: "Filtrar por lugar de procedencia"
            ),
            new OA\Parameter(
                name: "startDate",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "string", format: "date"),
                description: "Fecha de inicio"
            ),
            new OA\Parameter(
                name: "endDate",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "string", format: "date"),
                description: "Fecha de fin"
            ),
        ];
    }

    /**
     * Query parameters for payments list endpoint
     */
    public static function paymentsListParams(): array
    {
        return [
            new OA\Parameter(
                name: "page",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "integer", default: 1),
                description: "Número de página"
            ),
            new OA\Parameter(
                name: "limit",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "integer", default: 10),
                description: "Registros por página"
            ),
            new OA\Parameter(
                name: "clientId",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "string"),
                description: "Filtrar por cliente"
            ),
            new OA\Parameter(
                name: "startDate",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "string", format: "date"),
                description: "Fecha de inicio"
            ),
            new OA\Parameter(
                name: "endDate",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "string", format: "date"),
                description: "Fecha de fin"
            ),
        ];
    }

    /**
     * Query parameters for scraps/origins list endpoint
     */
    public static function simpleListParams(): array
    {
        return [
            new OA\Parameter(
                name: "page",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "integer", default: 1),
                description: "Número de página"
            ),
            new OA\Parameter(
                name: "limit",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "integer", default: 10),
                description: "Registros por página"
            ),
            new OA\Parameter(
                name: "search",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "string"),
                description: "Búsqueda por nombre o descripción"
            ),
        ];
    }

    /**
     * Search query parameter
     */
    public static function searchQueryParam(): OA\Parameter
    {
        return new OA\Parameter(
            name: "search",
            in: "query",
            required: false,
            schema: new OA\Schema(type: "string"),
            description: "Búsqueda por nombre o descripción"
        );
    }
}
