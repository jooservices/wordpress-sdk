<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Services;

use JOOservices\WordPress\Sdk\Endpoints\Endpoint;

/**
 * Global styles reads and updates (raw arrays, editor capability required).
 */
final class GlobalStylesService extends RawEndpointService
{
    /**
     * @param array<string, mixed> $query
     *
     * @return array<string, mixed>
     */
    public function list(array $query = []): array
    {
        return $this->getRaw(Endpoint::GLOBAL_STYLES->path(), $query);
    }

    /**
     * @return array<string, mixed>
     */
    public function get(string|int $id): array
    {
        return $this->getRaw(Endpoint::GLOBAL_STYLES->withId($id));
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function update(string|int $id, array $payload): array
    {
        return $this->postRaw(Endpoint::GLOBAL_STYLES->withId($id), $payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function theme(string $stylesheet): array
    {
        return $this->getRaw(Endpoint::GLOBAL_STYLES->path() . '/themes/' . $this->segment($stylesheet));
    }

    /** @return array<string, mixed> */
    public function variations(string $stylesheet): array
    {
        return $this->getRaw(Endpoint::GLOBAL_STYLES->path() . '/themes/' . $this->segment($stylesheet) . '/variations');
    }
}
