<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Data\Query;

use JOOservices\WordPress\Sdk\Enums\OrderDirection;
use JOOservices\WordPress\Sdk\Enums\PostStatus;
use JOOservices\WordPress\Sdk\Enums\RestContext;
use JOOservices\WordPress\Sdk\Enums\TaxRelation;

final class ListPostsQuery extends AbstractListQuery
{
    /**
     * @param list<int>|null $author
     * @param list<int>|null $authorExclude
     * @param list<int>|null $categories
     * @param list<int>|null $categoriesExclude
     * @param list<int>|null $tags
     * @param list<int>|null $tagsExclude
     * @param list<string>|null $slug
     * @param list<string>|null $searchColumns
     * @param list<int>|null $include
     * @param list<int>|null $exclude
     */
    public function __construct(
        public readonly ?array $author = null,
        public readonly ?array $authorExclude = null,
        public readonly ?array $categories = null,
        public readonly ?array $categoriesExclude = null,
        public readonly ?array $tags = null,
        public readonly ?array $tagsExclude = null,
        public readonly PostStatus|string|null $status = null,
        public readonly ?bool $sticky = null,
        public readonly ?string $after = null,
        public readonly ?string $before = null,
        public readonly ?string $modifiedAfter = null,
        public readonly ?string $modifiedBefore = null,
        public readonly ?array $slug = null,
        public readonly ?array $searchColumns = null,
        public readonly TaxRelation|string|null $taxRelation = null,
        public readonly ?string $format = null,
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
            'author' => $this->author,
            'author_exclude' => $this->authorExclude,
            'categories' => $this->categories,
            'categories_exclude' => $this->categoriesExclude,
            'tags' => $this->tags,
            'tags_exclude' => $this->tagsExclude,
            'status' => $this->scalar($this->status),
            'sticky' => $this->sticky,
            'after' => $this->after,
            'before' => $this->before,
            'modified_after' => $this->modifiedAfter,
            'modified_before' => $this->modifiedBefore,
            'slug' => $this->slug,
            'search_columns' => $this->searchColumns,
            'tax_relation' => $this->scalar($this->taxRelation),
            'format' => $this->format,
        ];
    }
}
