<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Services;

use JOOservices\WordPress\Sdk\Endpoints\Endpoint;

/**
 * Raw CRUD over TEMPLATES.
 */
final class TemplatesService extends RawEndpointService
{
    use RawCrud;

    protected function basePath(): string
    {
        return Endpoint::TEMPLATES->path();
    }

    /**
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     */
    public function lookup(array $query): array
    {
        return $this->getRaw(Endpoint::TEMPLATES->path() . '/lookup', $query);
    }
}
