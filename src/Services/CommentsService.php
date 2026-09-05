<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Services;

use JOOservices\WordPress\Sdk\Data\Comment;
use JOOservices\WordPress\Sdk\Endpoints\Endpoint;

/**
 * @extends AbstractCrudService<Comment>
 */
final class CommentsService extends AbstractCrudService
{
    protected function dtoClass(): string
    {
        return Comment::class;
    }

    protected function listPath(): string
    {
        return Endpoint::COMMENTS->path();
    }
}
