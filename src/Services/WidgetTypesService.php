<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Services;

use JOOservices\WordPress\Sdk\Endpoints\Endpoint;

/**
 * Widget type reads and encoding (raw arrays, editor capability required).
 */
final class WidgetTypesService extends RawEndpointService
{
    /**
     * @param array<string, mixed> $query
     *
     * @return array<string, mixed>
     */
    public function list(array $query = []): array
    {
        return $this->getRaw(Endpoint::WIDGET_TYPES->path(), $query);
    }

    /**
     * @return array<string, mixed>
     */
    public function get(string $id): array
    {
        return $this->getRaw(Endpoint::WIDGET_TYPES->withKey($id));
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function encode(string $id, array $payload): array
    {
        return $this->postRaw(Endpoint::WIDGET_TYPES->withKey($id) . '/encode', $payload);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function render(string $id, array $payload): array
    {
        return $this->postRaw(Endpoint::WIDGET_TYPES->withKey($id) . '/render', $payload);
    }
}
