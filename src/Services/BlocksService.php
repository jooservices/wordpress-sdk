<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Services;

use JOOservices\WordPress\Sdk\Endpoints\Endpoint;

/**
 * Raw CRUD over BLOCKS.
 */
final class BlocksService extends RawEndpointService
{
    use RawCrud;

    protected function basePath(): string
    {
        return Endpoint::BLOCKS->path();
    }
}
