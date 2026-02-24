<?php

namespace App;

use OpenApi\Attributes as OA;

if (!defined('L5_SWAGGER_CONST_HOST')) {
    define('L5_SWAGGER_CONST_HOST', env('APP_URL', 'http://localhost:8000'));
}

#[OA\Info(
    version: "1.0.0",
    title: "Pedidos Panchito API",
)]
#[OA\Server(
    url: L5_SWAGGER_CONST_HOST . "/api/v1",
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
#[OA\Tag(name: "Mesas", description: "Gestión de mesas")]
#[OA\Tag(name: "Pedidos", description: "Gestión de pedidos por mesa")]
#[OA\Tag(name: "Menú", description: "Gestión de platos y bebidas")]

class OpenApi
{
}
