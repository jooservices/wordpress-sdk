<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Data\Query;

use JOOservices\WordPress\Sdk\Data\Query\ListPostsQuery;
use JOOservices\WordPress\Sdk\Tests\TestCase;

final class AbstractListQueryTest extends TestCase
{
    public function testMapsBaseParametersToWordPressKeys(): void
    {
        $search = $this->faker->word();
        $query = new ListPostsQuery(
            page: 2,
            perPage: 10,
            search: $search,
            context: 'edit',
            orderby: 'date',
            order: 'desc',
            include: [1, 2],
            exclude: [3],
            fields: 'id,title',
            embed: true,
        );

        self::assertSame([
            'page' => 2, 'per_page' => 10, 'search' => $search, 'context' => 'edit',
            'orderby' => 'date', 'order' => 'desc', 'include' => [1, 2], 'exclude' => [3],
            '_fields' => 'id,title', '_embed' => 'true',
        ], $query->toQuery());
    }

    public function testFiltersNullEmptyAndFalseEmbedValues(): void
    {
        self::assertSame([], (new ListPostsQuery())->toQuery());
        self::assertSame([], (new ListPostsQuery(embed: false))->toQuery());
    }
}
