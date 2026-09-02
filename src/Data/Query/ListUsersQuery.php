<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Data\Query;

final class ListUsersQuery extends AbstractListQuery
{
    /**
     * @param list<string>|null $roles
     * @param list<string>|null $capabilities
     * @param list<int>|null $include
     * @param list<int>|null $exclude
     */
    public function __construct(
        public readonly ?array $roles = null,
        public readonly ?array $capabilities = null,
        public readonly ?bool $hasPublishedPosts = null,
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
            'roles' => $this->roles,
            'capabilities' => $this->capabilities,
            'has_published_posts' => $this->hasPublishedPosts,
        ];
    }
}
