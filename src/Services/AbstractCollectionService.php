<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Services;

use Generator;
use JOOservices\Dto\Core\Dto;
use JOOservices\WordPress\Sdk\Contracts\QueryParametersInterface;
use JOOservices\WordPress\Sdk\Contracts\Readable\ListableInterface;
use JOOservices\WordPress\Sdk\Http\AbstractService;
use JOOservices\WordPress\Sdk\Pagination\PaginatedCollection;

/**
 * Base for services that expose a typed paginated collection.
 *
 * Provides `list()` plus the `all()` / `cursor()` / `each()` streaming
 * helpers on top of one page-fetch primitive.
 *
 * @template TDto of Dto
 *
 * @implements ListableInterface<TDto>
 */
abstract class AbstractCollectionService extends AbstractService implements ListableInterface
{
    /**
     * @param array<string, mixed>|QueryParametersInterface|null $params
     *
     * @return PaginatedCollection<TDto>
     */
    public function list(array|QueryParametersInterface|null $params = null): PaginatedCollection
    {
        return $this->fetchPage($this->normalizeQueryParameters($params));
    }

    /**
     * Loads every matching item into memory. Prefer {@see cursor()} or
     * {@see each()} for large collections.
     *
     * @param array<string, mixed>|QueryParametersInterface|null $params
     *
     * @return list<TDto>
     */
    public function all(array|QueryParametersInterface|null $params = null): array
    {
        return $this->collectAll(
            $this->listPath(),
            $this->dtoClass(),
            $this->normalizeQueryParameters($params),
        );
    }

    /**
     * Streams matching items one page at a time.
     *
     * @param array<string, mixed>|QueryParametersInterface|null $params
     *
     * @return Generator<int, TDto>
     */
    public function cursor(array|QueryParametersInterface|null $params = null): Generator
    {
        return $this->cursorItems(
            $this->listPath(),
            $this->dtoClass(),
            $this->normalizeQueryParameters($params),
        );
    }

    /**
     * Iterates matching items; returning `false` from the callback stops
     * iteration early.
     *
     * @param callable(TDto): mixed $callback
     * @param array<string, mixed>|QueryParametersInterface|null $params
     */
    public function each(callable $callback, array|QueryParametersInterface|null $params = null): void
    {
        $this->eachItem(
            $this->listPath(),
            $this->dtoClass(),
            $callback,
            $this->normalizeQueryParameters($params),
        );
    }

    /**
     * @return class-string<TDto>
     */
    abstract protected function dtoClass(): string;

    /**
     * @param array<string, mixed> $query
     *
     * @return PaginatedCollection<TDto>
     */
    protected function fetchPage(array $query): PaginatedCollection
    {
        return $this->getList($this->listPath(), $this->dtoClass(), ['query' => $query]);
    }

    abstract protected function listPath(): string;
}
