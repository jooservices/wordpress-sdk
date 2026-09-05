<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Services;

use JOOservices\WordPress\Sdk\Http\AbstractService;
use JOOservices\WordPress\Sdk\Support\RestPath;

/**
 * Raw access to custom/plugin REST endpoints.
 *
 * Paths are relative to the configured WordPress REST root. Absolute URLs
 * and protocol-relative paths are rejected by {@see RestPath}.
 */
final class CustomEndpointService extends AbstractService
{
    /**
     * @param array<string, mixed> $query
     *
     * @return array<string, mixed>
     */
    public function get(string $path, array $query = []): array
    {
        return $this->send('GET', $path, query: $query);
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $query
     *
     * @return array<string, mixed>
     */
    public function post(string $path, array $payload = [], array $query = []): array
    {
        return $this->send('POST', $path, $payload, $query);
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $query
     *
     * @return array<string, mixed>
     */
    public function put(string $path, array $payload = [], array $query = []): array
    {
        return $this->send('PUT', $path, $payload, $query);
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $query
     *
     * @return array<string, mixed>
     */
    public function patch(string $path, array $payload = [], array $query = []): array
    {
        return $this->send('PATCH', $path, $payload, $query);
    }

    /**
     * @param array<string, mixed> $query
     *
     * @return array<string, mixed>
     */
    public function delete(string $path, array $query = []): array
    {
        return $this->send('DELETE', $path, query: $query);
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $query
     *
     * @return array<string, mixed>
     */
    private function send(string $method, string $path, array $payload = [], array $query = []): array
    {
        $options = [];

        if ($payload !== []) {
            $options['body'] = $payload;
        }

        if ($query !== []) {
            $options['query'] = $query;
        }

        return $this->requestArray($method, (new RestPath())->normalize($path), $options);
    }
}
