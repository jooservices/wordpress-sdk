<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Data\Query;

use JOOservices\WordPress\Sdk\Enums\OrderDirection;
use JOOservices\WordPress\Sdk\Enums\RestContext;

final class ListUsersQuery extends AbstractListQuery
{
    /**
     * @param list<string>|null $roles
     * @param list<string>|null $capabilities
     * @param list<string>|null $slug
     * @param list<int>|null $include
     * @param list<int>|null $exclude
     */
    public function __construct(
        public readonly ?array $roles = null,
        public readonly ?array $capabilities = null,
        public readonly ?bool $hasPublishedPosts = null,
        public readonly ?string $who = null,
        public readonly ?array $slug = null,
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
            'roles' => $this->roles,
            'capabilities' => $this->capabilities,
            'has_published_posts' => $this->hasPublishedPosts,
            'who' => $this->who,
            'slug' => $this->slug,
        ];
    }
}
