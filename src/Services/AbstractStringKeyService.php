<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Services;

use JOOservices\Dto\Core\Dto;
use JOOservices\WordPress\Sdk\Contracts\QueryParametersInterface;

/**
 * Base for read-only services keyed by a string slug (taxonomies, post
 * types, statuses).
 *
 * @template TDto of Dto
 *
 * @extends AbstractCollectionService<TDto>
 */
abstract class AbstractStringKeyService extends AbstractCollectionService
{
    /**
     * @param array<string, mixed>|QueryParametersInterface|null $params
     *
     * @return TDto
     */
    public function get(string $key, array|QueryParametersInterface|null $params = null): object
    {
        return $this->getItem(
            $this->listPath() . '/' . $key,
            $this->dtoClass(),
            ['query' => $this->normalizeQueryParameters($params)],
        );
    }
}
