<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Services;

use InvalidArgumentException;
use JOOservices\WordPress\Sdk\Http\AbstractService;

/** Autosaves for all core post-backed REST resources. */
final class AutosavesService extends AbstractService
{
    private const RESOURCES = [
        'posts', 'pages', 'blocks', 'templates', 'template-parts', 'navigation', 'menu-items',
    ];

    public function resource(string $resource, int|string $parentId): AutosaveResourceService
    {
        if (! in_array($resource, self::RESOURCES, true)) {
            throw new InvalidArgumentException('Unsupported autosave resource: ' . $resource);
        }

        $id = rawurlencode((string) $parentId);
        $path = 'wp/v2/' . $resource . '/' . $id . '/autosaves';

        return new AutosaveResourceService($this, $path);
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
