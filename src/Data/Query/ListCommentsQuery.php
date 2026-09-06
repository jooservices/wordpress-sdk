<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Data\Query;

use JOOservices\WordPress\Sdk\Enums\OrderDirection;
use JOOservices\WordPress\Sdk\Enums\RestContext;

final class ListCommentsQuery extends AbstractListQuery
{
    /**
     * @param list<int>|null $author
     * @param list<int>|null $authorExclude
     * @param list<int>|null $parentExclude
     * @param list<int>|null $include
     * @param list<int>|null $exclude
     */
    public function __construct(
        public readonly ?int $post = null,
        public readonly ?int $parent = null,
        public readonly ?array $parentExclude = null,
        public readonly ?string $status = null,
        public readonly ?string $type = null,
        public readonly ?array $author = null,
        public readonly ?array $authorExclude = null,
        public readonly ?string $authorEmail = null,
        public readonly ?string $after = null,
        public readonly ?string $before = null,
        public readonly ?string $password = null,
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
            'post' => $this->post,
            'parent' => $this->parent,
            'parent_exclude' => $this->parentExclude,
            'status' => $this->status,
            'type' => $this->type,
            'author' => $this->author,
            'author_exclude' => $this->authorExclude,
            'author_email' => $this->authorEmail,
            'after' => $this->after,
            'before' => $this->before,
            'password' => $this->password,
        ];
    }
}
