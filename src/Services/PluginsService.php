<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Services;

use JOOservices\WordPress\Sdk\Endpoints\Endpoint;

/**
 * Plugin management (raw arrays, admin capability required).
 */
final class PluginsService extends RawEndpointService
{
    /**
     * @param array<string, mixed> $query
     *
     * @return array<string, mixed>
     */
    public function list(array $query = []): array
    {
        return $this->getRaw(Endpoint::PLUGINS->path(), $query);
    }

    /**
     * Plugin ids are `directory/file.php` paths; each segment is encoded.
     *
     * @return array<string, mixed>
     */
    public function get(string $plugin): array
    {
        return $this->getRaw(Endpoint::PLUGINS->path() . '/' . $this->pluginPath($plugin));
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function create(array $payload): array
    {
        return $this->postRaw(Endpoint::PLUGINS->path(), $payload);
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function update(string $plugin, array $payload): array
    {
        return $this->postRaw(Endpoint::PLUGINS->path() . '/' . $this->pluginPath($plugin), $payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function delete(string $plugin): array
    {
        return $this->deleteRaw(Endpoint::PLUGINS->path() . '/' . $this->pluginPath($plugin));
    }

    private function pluginPath(string $plugin): string
    {
        return implode('/', array_map(
            fn(string $segment): string => $this->segment($segment),
            explode('/', trim($plugin, '/')),
        ));
    }
}
