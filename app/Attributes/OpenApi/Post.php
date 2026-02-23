<?php

namespace App\Attributes\OpenApi;

use Attribute;
use OpenApi\Attributes as OA;

/**
 * Custom attribute for POST endpoints
 * Simplifies OpenAPI annotation creation following SOLID principles
 */
#[Attribute(Attribute::TARGET_METHOD)]
class Post extends OA\Post
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
            new OA\Response(response: 201, description: "Recurso creado exitosamente"),
        ];

        if ($requiresAuth) {
            $defaultResponses[] = new OA\Response(response: 401, description: "No autenticado");
        }

        $defaultResponses[] = new OA\Response(response: 422, description: "Error de validación");

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
