<?php

namespace App\Attributes\OpenApi;

use Attribute;
use OpenApi\Attributes as OA;

/**
 * Custom attribute for PUT endpoints
 * Simplifies OpenAPI annotation creation following SOLID principles
 */
#[Attribute(Attribute::TARGET_METHOD)]
class Put extends OA\Put
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
        $providedResponseCodes = array_map(fn($r) => $r->response, $responses);

        $defaultResponses = [];
        
        if (!in_array(200, $providedResponseCodes)) {
            $defaultResponses[] = new OA\Response(response: 200, description: "Recurso actualizado exitosamente");
        }

        if (!in_array(404, $providedResponseCodes)) {
            $defaultResponses[] = new OA\Response(response: 404, description: "Recurso no encontrado");
        }

        if ($requiresAuth && !in_array(401, $providedResponseCodes)) {
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
