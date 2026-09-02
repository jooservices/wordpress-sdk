<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Services;

use JOOservices\WordPress\Sdk\Contracts\QueryParametersInterface;
use JOOservices\WordPress\Sdk\Data\SearchResult;
use JOOservices\WordPress\Sdk\Endpoints\Endpoint;
use JOOservices\WordPress\Sdk\Pagination\PaginatedCollection;

/**
 * @extends AbstractCollectionService<SearchResult>
 */
final class SearchService extends AbstractCollectionService
{
    /**
     * @param array<string, mixed>|QueryParametersInterface|null $params
     *
     * @return PaginatedCollection<SearchResult>
     */
    public function search(array|QueryParametersInterface|null $params = null): PaginatedCollection
    {
        return $this->list($params);
    }

    protected function dtoClass(): string
    {
        return SearchResult::class;
    }

    protected function listPath(): string
    {
        return Endpoint::SEARCH->path();
    }
}
