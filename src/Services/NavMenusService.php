<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Services;

use JOOservices\WordPress\Sdk\Endpoints\Endpoint;

/**
 * Raw CRUD over NAV_MENUS.
 */
final class NavMenusService extends RawEndpointService
{
    use RawCrud;

    protected function basePath(): string
    {
        return Endpoint::NAV_MENUS->path();
    }
}
