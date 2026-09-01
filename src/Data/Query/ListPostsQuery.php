<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Data\Query;

final class ListPostsQuery extends AbstractListQuery
{
    /**
     * @param list<int>|null $author
     * @param list<int>|null $authorExclude
     * @param list<int>|null $categories
     * @param list<int>|null $tags
     * @param list<int>|null $include
     * @param list<int>|null $exclude
     */
    public function __construct(
        public readonly ?array $author = null,
        public readonly ?array $authorExclude = null,
        public readonly ?array $categories = null,
        public readonly ?array $tags = null,
        public readonly ?string $status = null,
        public readonly ?bool $sticky = null,
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
            'author' => $this->author,
            'author_exclude' => $this->authorExclude,
            'categories' => $this->categories,
            'tags' => $this->tags,
            'status' => $this->status,
            'sticky' => $this->sticky,
        ];
    }
}
