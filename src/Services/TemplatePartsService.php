<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Services;

use JOOservices\WordPress\Sdk\Endpoints\Endpoint;

/**
 * Raw CRUD over TEMPLATE_PARTS.
 */
final class TemplatePartsService extends RawEndpointService
{
    use RawCrud;

    protected function basePath(): string
    {
        return Endpoint::TEMPLATE_PARTS->path();
    }
}
