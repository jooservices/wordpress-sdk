<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Data\Query;

use JOOservices\WordPress\Sdk\Enums\OrderDirection;
use JOOservices\WordPress\Sdk\Enums\RestContext;

final class SearchQuery extends AbstractListQuery
{
    /**
     * @param list<int>|null $include
     * @param list<int>|null $exclude
     */
    public function __construct(
        public readonly ?string $type = null,
        public readonly ?string $subtype = null,
        ?int $page = null,
        ?int $perPage = null,
        ?int $offset = null,
        ?string $search = null,
        RestContext|string|null $context = null,
        ?string $orderby = null,
        OrderDirection|string|null $order = null,
        ?array $include = null,
        ?array $exclude = null,
        ?string $fields = null,
        bool $embed = false,
    ) {
        parent::__construct(
            page: $page,
            perPage: $perPage,
            offset: $offset,
            search: $search,
            context: $context,
            orderby: $orderby,
            order: $order,
            include: $include,
            exclude: $exclude,
            fields: $fields,
            embed: $embed,
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function extraQuery(): array
    {
        return [
            'type' => $this->type,
            'subtype' => $this->subtype,
        ];
    }
}
