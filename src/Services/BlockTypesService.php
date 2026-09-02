<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Services;

use JOOservices\WordPress\Sdk\Endpoints\Endpoint;
use JOOservices\WordPress\Sdk\Support\RestPath;

/**
 * Block type reads (raw arrays, editor capability required).
 */
final class BlockTypesService extends RawEndpointService
{
    /**
     * @param array<string, mixed> $query
     *
     * @return array<string, mixed>
     */
    public function list(?string $namespace = null, array $query = []): array
    {
        $path = Endpoint::BLOCK_TYPES->path();

        if ($namespace !== null) {
            $path .= '/' . $this->segment($namespace);
        }

        return $this->getRaw($path, $query);
    }

    /**
     * @return array<string, mixed>
     */
    public function get(string $name): array
    {
        return $this->getRaw(Endpoint::BLOCK_TYPES->path() . '/' . (new RestPath())->normalize($name));
    }
}
