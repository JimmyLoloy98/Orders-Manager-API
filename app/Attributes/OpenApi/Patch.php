<?php

namespace App\Attributes\OpenApi;

use Attribute;
use OpenApi\Attributes as OA;

/**
 * Custom attribute for PATCH endpoints
 * Simplifies OpenAPI annotation creation following SOLID principles
 */
#[Attribute(Attribute::TARGET_METHOD)]
class Patch extends OA\Patch
{
    public function __construct(
        string $path,
        string $summary,
        string $tag,
        bool $requiresAuth = true,
        $requestBody = null,
        array $responses = [],
        array $parameters = []
    ) {
        $defaultResponses = [
            new OA\Response(response: 200, description: "Recurso actualizado exitosamente"),
            new OA\Response(response: 404, description: "Recurso no encontrado"),
        ];

        if ($requiresAuth) {
            $defaultResponses[] = new OA\Response(response: 401, description: "No autenticado");
        }

        $responses = array_merge($defaultResponses, $responses);

        parent::__construct(
            path: $path,
            summary: $summary,
            tags: [$tag],
            security: $requiresAuth ? [["bearerAuth" => []]] : [],
            requestBody: $requestBody,
            responses: $responses,
            parameters: $parameters
        );
    }
}
