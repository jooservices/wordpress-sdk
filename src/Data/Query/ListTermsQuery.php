<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Data\Query;

use JOOservices\WordPress\Sdk\Enums\OrderDirection;
use JOOservices\WordPress\Sdk\Enums\RestContext;

final class ListTermsQuery extends AbstractListQuery
{
    /**
     * @param list<string>|null $slug
     * @param list<int>|null $include
     * @param list<int>|null $exclude
     */
    public function __construct(
        public readonly ?bool $hideEmpty = null,
        public readonly ?int $parent = null,
        public readonly ?int $post = null,
        public readonly ?array $slug = null,
        ?int $page = null,
        ?int $perPage = null,
        ?string $search = null,
        RestContext|string|null $context = null,
        ?string $orderby = null,
        OrderDirection|string|null $order = null,
        ?array $include = null,
        ?array $exclude = null,
        ?string $fields = null,
        bool $embed = false,
        ?int $offset = null,
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
            offset: $offset,
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function extraQuery(): array
    {
        return [
            'hide_empty' => $this->hideEmpty,
            'parent' => $this->parent,
            'post' => $this->post,
            'slug' => $this->slug,
        ];
    }
}
