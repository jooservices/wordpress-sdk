<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Services;

use JOOservices\WordPress\Sdk\Endpoints\Endpoint;

/**
 * Raw CRUD over WIDGETS.
 */
final class WidgetsService extends RawEndpointService
{
    use RawCrud;

    protected function basePath(): string
    {
        return Endpoint::WIDGETS->path();
    }
}
