<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Data\Query;

use JOOservices\Dto\Core\Dto;
use JOOservices\WordPress\Sdk\Contracts\QueryParametersInterface;

/**
 * Base query DTO for list/read operations.
 *
 * Subclasses promote their own filter parameters and return them from
 * {@see extraQuery()}; the WordPress key mapping and null/empty filtering
 * lives here once.
 */
abstract class AbstractListQuery extends Dto implements QueryParametersInterface
{
    /**
     * @param list<int>|null $include
     * @param list<int>|null $exclude
     */
    public function __construct(
        public readonly ?int $page = null,
        public readonly ?int $perPage = null,
        public readonly ?string $search = null,
        public readonly ?string $context = null,
        public readonly ?string $orderby = null,
        public readonly ?string $order = null,
        public readonly ?array $include = null,
        public readonly ?array $exclude = null,
        public readonly ?string $fields = null,
        public readonly bool $embed = false,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toQuery(): array
    {
        return array_filter([
            'page' => $this->page,
            'per_page' => $this->perPage,
            'search' => $this->search,
            'context' => $this->context,
            'orderby' => $this->orderby,
            'order' => $this->order,
            'include' => $this->include,
            'exclude' => $this->exclude,
            '_fields' => $this->fields,
            '_embed' => $this->embed ? 'true' : null,
            ...$this->extraQuery(),
        ], static fn(mixed $value): bool => $value !== null && $value !== []);
    }

    /**
     * @return array<string, mixed>
     */
    protected function extraQuery(): array
    {
        return [];
    }
}
