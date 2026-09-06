<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Services;

use JOOservices\WordPress\Sdk\Data\Post;
use JOOservices\WordPress\Sdk\Endpoints\Endpoint;
use JOOservices\WordPress\Sdk\Support\PostBuilder;

/**
 * @extends AbstractCrudService<Post>
 */
final class PostsService extends AbstractCrudService
{
    private ?MediaService $mediaService = null;

    public function setMediaService(MediaService $mediaService): void
    {
        $this->mediaService = $mediaService;
    }

    public function builder(): PostBuilder
    {
        return new PostBuilder($this, $this->mediaService);
    }

    public function revisions(int $id): RevisionResourceService
    {
        return new RevisionResourceService(
            new RevisionsService($this->client, $this->requestBuilder, $this->decoder, $this->errorMapper),
            Endpoint::POSTS->withChild($id, 'revisions'),
        );
    }

    public function autosaves(int $id): AutosaveResourceService
    {
        return new AutosaveResourceService(
            new AutosavesService($this->client, $this->requestBuilder, $this->decoder, $this->errorMapper),
            Endpoint::POSTS->withChild($id, 'autosaves'),
        );
    }

    protected function dtoClass(): string
    {
        return Post::class;
    }

    protected function listPath(): string
    {
        return Endpoint::POSTS->path();
    }
}
