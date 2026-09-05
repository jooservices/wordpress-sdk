<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Services;

use JOOservices\WordPress\Sdk\Http\AbstractService;
use JOOservices\WordPress\Sdk\Support\PostBackedResources;
use JOOservices\WordPress\Sdk\Support\RestPath;

/** Autosaves for all core post-backed REST resources. */
final class AutosavesService extends AbstractService
{
    public function resource(string $resource, int|string $parentId): AutosaveResourceService
    {
        $resource = (new RestPath())->normalize($resource);
        PostBackedResources::assertSupported($resource, 'autosave');

        return new AutosaveResourceService(
            $this,
            PostBackedResources::childPath($resource, $parentId, 'autosaves'),
        );
    }

    /**
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     */
    public function listPath(string $path, array $query = []): array
    {
        return $this->requestArray('GET', $path, $query === [] ? [] : ['query' => $query]);
    }

    /** @return array<string, mixed> */
    public function getPath(string $path, int $id): array
    {
        return $this->requestArray('GET', $path . '/' . $id);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function createPath(string $path, array $payload): array
    {
        return $this->requestArray('POST', $path, ['body' => $payload]);
    }
}
