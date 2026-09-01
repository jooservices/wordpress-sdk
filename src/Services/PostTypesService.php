<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Services;

use JOOservices\WordPress\Sdk\Data\PostType;
use JOOservices\WordPress\Sdk\Endpoints\Endpoint;

/**
 * @extends AbstractStringKeyService<PostType>
 */
final class PostTypesService extends AbstractStringKeyService
{
    protected function dtoClass(): string
    {
        return PostType::class;
    }

    protected function listPath(): string
    {
        return Endpoint::POST_TYPES->path();
    }
}
