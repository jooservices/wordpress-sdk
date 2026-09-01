<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Services;

use JOOservices\WordPress\Sdk\Data\Term;
use JOOservices\WordPress\Sdk\Endpoints\Endpoint;

/**
 * @extends AbstractCrudService<Term>
 */
final class TagsService extends AbstractCrudService
{
    protected function dtoClass(): string
    {
        return Term::class;
    }

    protected function listPath(): string
    {
        return Endpoint::TAGS->path();
    }
}
