<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Services;

use JOOservices\WordPress\Sdk\Endpoints\Endpoint;

/**
 * Raw CRUD over NAVIGATIONS.
 */
final class NavigationsService extends RawEndpointService
{
    use RawCrud;

    protected function basePath(): string
    {
        return Endpoint::NAVIGATIONS->path();
    }
}
