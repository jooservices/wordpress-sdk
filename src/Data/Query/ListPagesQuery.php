<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Data\Query;

final class ListPagesQuery extends AbstractListQuery
{
    /**
     * @param list<int>|null $parentExclude
     * @param list<int>|null $include
     * @param list<int>|null $exclude
     */
    public function __construct(
        public readonly ?int $parent = null,
        public readonly ?array $parentExclude = null,
        public readonly ?string $status = null,
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
            'parent' => $this->parent,
            'parent_exclude' => $this->parentExclude,
            'status' => $this->status,
        ];
    }
}
