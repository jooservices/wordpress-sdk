<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Services;

use JOOservices\WordPress\Sdk\Endpoints\Endpoint;
use JOOservices\WordPress\Sdk\Http\AbstractService;
use JOOservices\WordPress\Sdk\Support\PostBackedResources;
use JOOservices\WordPress\Sdk\Support\RestPath;

/**
 * Revisions for all core post-backed REST resources (raw arrays).
 */
final class RevisionsService extends AbstractService
{
    public function posts(int $postId): RevisionResourceService
    {
        return new RevisionResourceService($this, Endpoint::POSTS->withChild($postId, 'revisions'));
    }

    public function pages(int $pageId): RevisionResourceService
    {
        return new RevisionResourceService($this, Endpoint::PAGES->withChild($pageId, 'revisions'));
    }

    public function blocks(int $blockId): RevisionResourceService
    {
        return new RevisionResourceService($this, Endpoint::BLOCKS->withChild($blockId, 'revisions'));
    }

    public function resource(string $resource, int|string $parentId): RevisionResourceService
    {
        $resource = (new RestPath())->normalize($resource);
        PostBackedResources::assertSupported($resource, 'revision');

        return new RevisionResourceService(
            $this,
            PostBackedResources::childPath($resource, $parentId, 'revisions'),
        );
    }

    /**
     * @param array<string, mixed> $query
     *
     * @return array<string, mixed>
     */
    public function listPath(string $path, array $query = []): array
    {
        return $this->requestArray('GET', $path, $query === [] ? [] : ['query' => $query]);
    }

    /**
     * @return array<string, mixed>
     */
    public function getPath(string $path, int $revisionId): array
    {
        return $this->requestArray('GET', $path . '/' . $revisionId);
    }

    /**
     * @return array<string, mixed>
     */
    public function deletePath(string $path, int $revisionId): array
    {
        return $this->requestArray('DELETE', $path . '/' . $revisionId);
    }
}
