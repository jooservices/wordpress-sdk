<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Services;

use JOOservices\WordPress\Sdk\Endpoints\Endpoint;

/** WordPress pattern registry, directory, and pattern-category term APIs. */
final class PatternsService extends RawEndpointService
{
    /**
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     */
    public function patterns(array $query = []): array
    {
        return $this->getRaw(Endpoint::BLOCK_PATTERNS->path(), $query);
    }

    /**
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     */
    public function categories(array $query = []): array
    {
        return $this->getRaw(Endpoint::BLOCK_PATTERN_CATEGORIES->path(), $query);
    }

    /**
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     */
    public function directory(array $query = []): array
    {
        return $this->getRaw(Endpoint::PATTERN_DIRECTORY->path(), $query);
    }

    /**
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     */
    public function listTerms(array $query = []): array
    {
        return $this->getRaw(Endpoint::PATTERN_CATEGORIES->path(), $query);
    }

    /** @return array<string, mixed> */
    public function getTerm(int $id): array
    {
        return $this->getRaw(Endpoint::PATTERN_CATEGORIES->withId($id));
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function createTerm(array $payload): array
    {
        return $this->postRaw(Endpoint::PATTERN_CATEGORIES->path(), $payload);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function updateTerm(int $id, array $payload): array
    {
        return $this->postRaw(Endpoint::PATTERN_CATEGORIES->withId($id), $payload);
    }

    /** @return array<string, mixed> */
    public function deleteTerm(int $id, bool $force = true): array
    {
        return $this->deleteRaw(Endpoint::PATTERN_CATEGORIES->withId($id), $force ? ['force' => 'true'] : []);
    }
}
