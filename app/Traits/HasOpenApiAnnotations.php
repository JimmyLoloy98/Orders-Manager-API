<?php

namespace App\Traits;

use App\Helpers\OpenApiHelper;

/**
 * Trait to simplify OpenAPI annotation creation in controllers
 * Provides convenient methods that delegate to OpenApiHelper
 */
trait HasOpenApiAnnotations
{
    /**
     * Create a GET endpoint annotation
     */
    protected function getEndpoint(
        string $path,
        string $summary,
        string $tag,
        bool $requiresAuth = true,
        array $parameters = [],
        array $responses = []
    ) {
        return OpenApiHelper::get($path, $summary, $tag, $requiresAuth, $parameters, $responses);
    }

    /**
     * Create a POST endpoint annotation
     */
    protected function postEndpoint(
        string $path,
        string $summary,
        string $tag,
        bool $requiresAuth = true,
        $requestBody = null,
        array $responses = []
    ) {
        return OpenApiHelper::post($path, $summary, $tag, $requiresAuth, $requestBody, $responses);
    }

    /**
     * Create a PUT endpoint annotation
     */
    protected function putEndpoint(
        string $path,
        string $summary,
        string $tag,
        bool $requiresAuth = true,
        $requestBody = null,
        array $responses = []
    ) {
        return OpenApiHelper::put($path, $summary, $tag, $requiresAuth, $requestBody, $responses);
    }

    /**
     * Create a PATCH endpoint annotation
     */
    protected function patchEndpoint(
        string $path,
        string $summary,
        string $tag,
        bool $requiresAuth = true,
        $requestBody = null,
        array $responses = []
    ) {
        return OpenApiHelper::patch($path, $summary, $tag, $requiresAuth, $requestBody, $responses);
    }

    /**
     * Create a DELETE endpoint annotation
     */
    protected function deleteEndpoint(
        string $path,
        string $summary,
        string $tag,
        bool $requiresAuth = true,
        array $responses = []
    ) {
        return OpenApiHelper::delete($path, $summary, $tag, $requiresAuth, $responses);
    }
}
