<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Services;

use JOOservices\WordPress\Sdk\Endpoints\Endpoint;

/**
 * Sidebar reads and updates (raw arrays, editor capability required).
 */
final class SidebarsService extends RawEndpointService
{
    /**
     * @param array<string, mixed> $query
     *
     * @return array<string, mixed>
     */
    public function list(array $query = []): array
    {
        return $this->getRaw(Endpoint::SIDEBARS->path(), $query);
    }

    /**
     * @return array<string, mixed>
     */
    public function get(string $id): array
    {
        return $this->getRaw(Endpoint::SIDEBARS->withKey($id));
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function update(string $id, array $payload): array
    {
        return $this->postRaw(Endpoint::SIDEBARS->withKey($id), $payload);
    }
}
