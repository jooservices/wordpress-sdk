<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Services;

use JOOservices\WordPress\Sdk\Data\Page;
use JOOservices\WordPress\Sdk\Endpoints\Endpoint;

/**
 * @extends AbstractCrudService<Page>
 */
final class PagesService extends AbstractCrudService
{
    protected function dtoClass(): string
    {
        return Page::class;
    }

    protected function listPath(): string
    {
        return Endpoint::PAGES->path();
    }
}
