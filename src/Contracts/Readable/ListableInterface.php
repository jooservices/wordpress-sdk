<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Contracts\Readable;

use JOOservices\WordPress\Sdk\Contracts\QueryParametersInterface;
use JOOservices\WordPress\Sdk\Pagination\PaginatedCollection;

/**
 * @template TDto of object
 */
interface ListableInterface
{
    /**
     * @param array<string, mixed>|QueryParametersInterface|null $params
     *
     * @return PaginatedCollection<TDto>
     */
    public function list(array|QueryParametersInterface|null $params = null): PaginatedCollection;
}
