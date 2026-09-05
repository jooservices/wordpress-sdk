<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Services;

use JOOservices\WordPress\Sdk\Endpoints\Endpoint;

/**
 * Block directory search (`/wp/v2/block-directory/search`).
 */
final class BlockDirectoryService extends RawEndpointService
{
    /**
     * @param array<string, mixed> $query
     *
     * @return array<string, mixed>
     */
    public function search(array $query = []): array
    {
        return $this->getRaw(Endpoint::BLOCK_DIRECTORY->path(), $query);
    }
}
