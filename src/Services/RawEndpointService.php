<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Services;

use JOOservices\WordPress\Sdk\Http\AbstractService;

/**
 * Base for services that operate on raw response arrays (admin and editor
 * endpoint groups whose schemas are not stable enough for public DTOs).
 */
abstract class RawEndpointService extends AbstractService
{
    /**
     * @param array<string, mixed> $query
     *
     * @return array<string, mixed>
     */
    protected function getRaw(string $path, array $query = []): array
    {
        return $this->requestArray('GET', $path, $query === [] ? [] : ['query' => $query]);
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    protected function postRaw(string $path, array $payload = []): array
    {
        return $this->requestArray('POST', $path, $payload === [] ? [] : ['body' => $payload]);
    }

    /**
     * @param array<string, mixed> $query
     *
     * @return array<string, mixed>
     */
    protected function deleteRaw(string $path, array $query = []): array
    {
        return $this->requestArray('DELETE', $path, $query === [] ? [] : ['query' => $query]);
    }

    protected function segment(int|string $value): string
    {
        return rawurlencode((string) $value);
    }
}
