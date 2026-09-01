<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Services;

use JOOservices\WordPress\Sdk\Endpoints\Endpoint;

/** Batch and oEmbed routes that sit outside the wp/v2 namespace. */
final class UtilityService extends RawEndpointService
{
    /**
     * @param list<array<string, mixed>> $requests
     * @return array<string, mixed>
     */
    public function batch(array $requests, ?string $validation = null): array
    {
        $payload = ['requests' => $requests];
        if ($validation !== null) {
            $payload['validation'] = $validation;
        }

        return $this->postRaw(Endpoint::BATCH->path(), $payload);
    }

    /**
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     */
    public function embed(string $url, array $query = []): array
    {
        return $this->getRaw(Endpoint::OEMBED->path() . '/embed', ['url' => $url, ...$query]);
    }
    /**
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     */
    public function proxy(string $url, array $query = []): array
    {
        return $this->getRaw(Endpoint::OEMBED->path() . '/proxy', ['url' => $url, ...$query]);
    }
}
