<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Services;

/**
 * Scoped revision resource (posts, pages, or block autosaves).
 */
final readonly class RevisionResourceService
{
    public function __construct(
        private RevisionsService $service,
        private string $path,
    ) {}

    /**
     * @param array<string, mixed> $query
     *
     * @return array<string, mixed>
     */
    public function list(array $query = []): array
    {
        return $this->service->listPath($this->path, $query);
    }

    /**
     * @return array<string, mixed>
     */
    public function get(int $revisionId): array
    {
        return $this->service->getPath($this->path, $revisionId);
    }

    /**
     * @return array<string, mixed>
     */
    public function delete(int $revisionId): array
    {
        return $this->service->deletePath($this->path, $revisionId);
    }
}
