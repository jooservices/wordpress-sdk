<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Services;

use JOOservices\WordPress\Sdk\Data\Taxonomy;
use JOOservices\WordPress\Sdk\Endpoints\Endpoint;

/**
 * @extends AbstractStringKeyService<Taxonomy>
 */
final class TaxonomiesService extends AbstractStringKeyService
{
    protected function dtoClass(): string
    {
        return Taxonomy::class;
    }

    protected function listPath(): string
    {
        return Endpoint::TAXONOMIES->path();
    }
}
