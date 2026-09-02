<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Services;

final readonly class AutosaveResourceService
{
    public function __construct(private AutosavesService $service, private string $path) {}

    /**
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     */
    public function list(array $query = []): array
    {
        return $this->service->listPath($this->path, $query);
    }
    /** @return array<string, mixed> */
    public function get(int $id): array
    {
        return $this->service->getPath($this->path, $id);
    }
    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function create(array $payload): array
    {
        return $this->service->createPath($this->path, $payload);
    }
}
