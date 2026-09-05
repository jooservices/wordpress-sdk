<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Services;

use JOOservices\WordPress\Sdk\Endpoints\Endpoint;

/**
 * Raw CRUD over NAV_MENU_ITEMS.
 */
final class NavMenuItemsService extends RawEndpointService
{
    use RawCrud;

    protected function basePath(): string
    {
        return Endpoint::NAV_MENU_ITEMS->path();
    }
}
