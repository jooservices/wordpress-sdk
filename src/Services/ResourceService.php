<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Services;

use JOOservices\Client\Request\RequestBuilder;
use JOOservices\WordPress\Sdk\Contracts\ResponseDecoderInterface;
use JOOservices\WordPress\Sdk\Data\Post;
use JOOservices\WordPress\Sdk\Http\ErrorMapper;
use Psr\Http\Client\ClientInterface;

/**
 * Typed CRUD against a WordPress REST collection that uses the post schema
 * (core posts or a `show_in_rest` custom post type).
 *
 * @extends AbstractCrudService<Post>
 */
final class ResourceService extends AbstractCrudService
{
    public function __construct(
        ClientInterface $client,
        RequestBuilder $requestBuilder,
        ResponseDecoderInterface $decoder,
        ErrorMapper $errorMapper,
        private readonly string $path,
    ) {
        parent::__construct($client, $requestBuilder, $decoder, $errorMapper);
    }

    public function revisions(int $id): RevisionResourceService
    {
        return new RevisionResourceService(
            new RevisionsService($this->client, $this->requestBuilder, $this->decoder, $this->errorMapper),
            $this->listPath() . '/' . $id . '/revisions',
        );
    }

    public function autosaves(int $id): AutosaveResourceService
    {
        return new AutosaveResourceService(
            new AutosavesService($this->client, $this->requestBuilder, $this->decoder, $this->errorMapper),
            $this->listPath() . '/' . $id . '/autosaves',
        );
    }

    protected function dtoClass(): string
    {
        return Post::class;
    }

    protected function listPath(): string
    {
        return $this->path;
    }
}
