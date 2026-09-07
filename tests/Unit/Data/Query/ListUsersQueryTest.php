<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Data\Query;

use JOOservices\WordPress\Sdk\Data\Query\ListUsersQuery;
use JOOservices\WordPress\Sdk\Tests\TestCase;

final class ListUsersQueryTest extends TestCase
{
    public function testMapsUserParameters(): void
    {
        self::assertSame(
            ['roles' => ['editor'], 'capabilities' => ['edit_posts'], 'has_published_posts' => true],
            (new ListUsersQuery(roles: ['editor'], capabilities: ['edit_posts'], hasPublishedPosts: true))->toQuery(),
        );
    }

    public function testOffsetPreservesLegacyPositionalArguments(): void
    {
        $search = $this->faker->word();
        $query = new ListUsersQuery(
            null,
            null,
            null,
            null,
            null,
            2,
            10,
            $search,
            'edit',
            'name',
            'asc',
            null,
            null,
            null,
            false,
            5,
        );

        self::assertSame([
            'page' => 2, 'per_page' => 10, 'offset' => 5, 'search' => $search,
            'context' => 'edit', 'orderby' => 'name', 'order' => 'asc',
        ], $query->toQuery());
    }
}
