<?php

namespace App;

use OpenApi\Attributes as OA;

if (!defined('L5_SWAGGER_CONST_HOST')) {
    define('L5_SWAGGER_CONST_HOST', env('APP_URL', 'http://localhost:8000'));
}

#[OA\Info(
    version: "1.0.0",
    title: "Scrap Payments Manager API",
    description: "API documentation for Scrap Payments Manager application"
)]
#[OA\Server(
    url: L5_SWAGGER_CONST_HOST . "api/v1",
    description: "API Server"
)]
#[OA\SecurityScheme(
    securityScheme: "bearerAuth",
    type: "http",
    name: "Authorization",
    in: "header",
    scheme: "bearer",
    bearerFormat: "JWT"
)]
#[OA\Tag(name: "Authentication", description: "Authentication endpoints")]
#[OA\Tag(name: "Dashboard", description: "Dashboard statistics and overview")]
#[OA\Tag(name: "Clients", description: "Client management")]
#[OA\Tag(name: "Credits", description: "Credit management")]
#[OA\Tag(name: "Payments", description: "Payment management")]
#[OA\Tag(name: "Scraps", description: "Scrap types management")]
#[OA\Tag(name: "Origins", description: "Origin locations management")]
class OpenApi
{
}
