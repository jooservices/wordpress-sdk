<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Data\Query;

use JOOservices\Dto\Core\Dto;
use JOOservices\WordPress\Sdk\Contracts\QueryParametersInterface;
use JOOservices\WordPress\Sdk\Enums\OrderDirection;
use JOOservices\WordPress\Sdk\Enums\RestContext;
use JOOservices\WordPress\Sdk\Support\MapsScalarQuery;

/**
 * Base query DTO for list/read operations.
 *
 * Subclasses promote their own filter parameters and return them from
 * {@see extraQuery()}; the WordPress key mapping and null/empty filtering
 * lives here once.
 */
abstract class AbstractListQuery extends Dto implements QueryParametersInterface
{
    use MapsScalarQuery;

    /**
     * @param list<int>|null $include
     * @param list<int>|null $exclude
     */
    public function __construct(
        public readonly ?int $page = null,
        public readonly ?int $perPage = null,
        public readonly ?string $search = null,
        public readonly RestContext|string|null $context = null,
        public readonly ?string $orderby = null,
        public readonly OrderDirection|string|null $order = null,
        public readonly ?array $include = null,
        public readonly ?array $exclude = null,
        public readonly ?string $fields = null,
        public readonly bool $embed = false,
        public readonly ?int $offset = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toQuery(): array
    {
        return $this->omitEmpty([
            'page' => $this->page,
            'per_page' => $this->perPage,
            'offset' => $this->offset,
            'search' => $this->search,
            'context' => $this->scalar($this->context),
            'orderby' => $this->orderby,
            'order' => $this->scalar($this->order),
            'include' => $this->include,
            'exclude' => $this->exclude,
            '_fields' => $this->fields,
            '_embed' => $this->embed ? 'true' : null,
            ...$this->extraQuery(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function extraQuery(): array
    {
        return [];
    }
}
