<?php

namespace App\Attributes\OpenApi;

use Attribute;
use OpenApi\Attributes as OA;

/**
 * Custom attribute for GET endpoints
 * Simplifies OpenAPI annotation creation following SOLID principles
 * 
 * Note: Parameters must be passed as actual objects, not method calls
 */
#[Attribute(Attribute::TARGET_METHOD)]
class Get extends OA\Get
{
    public function __construct(
        string $path,
        string $summary,
        string $tag,
        bool $requiresAuth = true,
        array $parameters = [],
        array $responses = []
    ) {
        $defaultResponses = [
            new OA\Response(response: 200, description: "Operación exitosa"),
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
            parameters: $parameters,
            responses: $responses
        );
    }
}
