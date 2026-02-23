<?php

namespace App\Helpers;

use OpenApi\Attributes as OA;

/**
 * Static parameter definitions for OpenAPI
 * These are constants that can be used in attributes
 */
class OpenApiParams
{
    // Common parameters
    public const PAGE_PARAM = 'page';
    public const LIMIT_PARAM = 'limit';
    public const SEARCH_PARAM = 'search';
    public const ID_PARAM = 'id';
}
