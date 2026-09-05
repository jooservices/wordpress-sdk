<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Pagination;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * A paginated WordPress collection.
 *
 * `total` and `totalPages` come from the `X-WP-Total` and `X-WP-TotalPages`
 * response headers; the items are the current page.
 *
 * @template TDto of object
 *
 * @implements IteratorAggregate<int, TDto>
 */
class PaginatedCollection implements IteratorAggregate, Countable
{
    /**
     * @param list<TDto> $items
     */
    public function __construct(
        private readonly array $items,
        public readonly int $total,
        public readonly int $totalPages,
    ) {}

    /**
     * @return Traversable<int, TDto>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    public function count(): int
    {
        return count($this->items);
    }

    /**
     * @return list<TDto>
     */
    public function all(): array
    {
        return $this->items;
    }
}
