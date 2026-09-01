<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Services;

use JOOservices\WordPress\Sdk\Endpoints\Endpoint;

/**
 * Theme reads (raw arrays, admin capability required).
 */
final class ThemesService extends RawEndpointService
{
    /**
     * @param array<string, mixed> $query
     *
     * @return array<string, mixed>
     */
    public function list(array $query = []): array
    {
        return $this->getRaw(Endpoint::THEMES->path(), $query);
    }

    /**
     * @return array<string, mixed>
     */
    public function get(string $stylesheet): array
    {
        return $this->getRaw(Endpoint::THEMES->withKey($stylesheet));
    }
}
