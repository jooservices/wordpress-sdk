<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Services;

use JOOservices\WordPress\Sdk\Http\AbstractService;
use JOOservices\WordPress\Sdk\Support\RestPath;

/**
 * REST discovery helpers: the index, registered routes, and per-route schema.
 */
final class DiscoveryService extends AbstractService
{
    /**
     * @return array<string, mixed>
     */
    public function index(): array
    {
        return $this->requestArray('GET', '.');
    }

    /**
     * @return array<string, mixed>
     */
    public function routes(): array
    {
        $index = $this->index();
        $routes = $index['routes'] ?? null;

        /** @var array<string, mixed> $routes */
        return is_array($routes) ? $routes : [];
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(string $path): array
    {
        return $this->requestArray('OPTIONS', (new RestPath())->normalize($path));
    }
}
