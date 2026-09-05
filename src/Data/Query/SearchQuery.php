<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Data\Query;

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
        ?string $search = null,
        ?string $context = null,
        ?string $orderby = null,
        ?string $order = null,
        ?array $include = null,
        ?array $exclude = null,
        ?string $fields = null,
        bool $embed = false,
    ) {
        parent::__construct(
            page: $page,
            perPage: $perPage,
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
