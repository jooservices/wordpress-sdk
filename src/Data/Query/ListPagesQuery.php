<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Data\Query;

use JOOservices\WordPress\Sdk\Enums\OrderDirection;
use JOOservices\WordPress\Sdk\Enums\PostStatus;
use JOOservices\WordPress\Sdk\Enums\RestContext;

final class ListPagesQuery extends AbstractListQuery
{
    /**
     * @param list<int>|null $parentExclude
     * @param list<int>|null $author
     * @param list<int>|null $authorExclude
     * @param list<string>|null $slug
     * @param list<int>|null $include
     * @param list<int>|null $exclude
     */
    public function __construct(
        public readonly ?int $parent = null,
        public readonly ?array $parentExclude = null,
        public readonly PostStatus|string|null $status = null,
        public readonly ?array $author = null,
        public readonly ?array $authorExclude = null,
        public readonly ?string $after = null,
        public readonly ?string $before = null,
        public readonly ?string $modifiedAfter = null,
        public readonly ?string $modifiedBefore = null,
        public readonly ?array $slug = null,
        public readonly ?int $menuOrder = null,
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
            'parent' => $this->parent,
            'parent_exclude' => $this->parentExclude,
            'status' => $this->scalar($this->status),
            'author' => $this->author,
            'author_exclude' => $this->authorExclude,
            'after' => $this->after,
            'before' => $this->before,
            'modified_after' => $this->modifiedAfter,
            'modified_before' => $this->modifiedBefore,
            'slug' => $this->slug,
            'menu_order' => $this->menuOrder,
        ];
    }
}
