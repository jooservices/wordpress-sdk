<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Data\Query;

use JOOservices\WordPress\Sdk\Data\Query\ListPostsQuery;
use JOOservices\WordPress\Sdk\Enums\PostStatus;
use JOOservices\WordPress\Sdk\Enums\TaxRelation;
use JOOservices\WordPress\Sdk\Tests\TestCase;

final class ListPostsQueryTest extends TestCase
{
    public function testMapsPostParameters(): void
    {
        $slug = $this->faker->slug();
        $query = new ListPostsQuery(
            author: [1],
            authorExclude: [2],
            categories: [3],
            categoriesExclude: [9],
            tags: [4],
            tagsExclude: [8],
            status: PostStatus::Publish,
            sticky: true,
            after: '2026-01-01T00:00:00',
            before: '2026-12-31T23:59:59',
            modifiedAfter: '2026-02-01T00:00:00',
            modifiedBefore: '2026-11-01T00:00:00',
            slug: [$slug],
            searchColumns: ['post_title'],
            taxRelation: TaxRelation::And,
            format: 'standard',
            offset: 20,
        );

        self::assertSame([
            'offset' => 20, 'author' => [1], 'author_exclude' => [2], 'categories' => [3],
            'categories_exclude' => [9], 'tags' => [4], 'tags_exclude' => [8], 'status' => 'publish',
            'sticky' => true, 'after' => '2026-01-01T00:00:00', 'before' => '2026-12-31T23:59:59',
            'modified_after' => '2026-02-01T00:00:00', 'modified_before' => '2026-11-01T00:00:00',
            'slug' => [$slug], 'search_columns' => ['post_title'], 'tax_relation' => 'AND', 'format' => 'standard',
        ], $query->toQuery());
    }

    public function testCombinesBaseAndPostParameters(): void
    {
        self::assertSame(
            ['per_page' => 20, '_fields' => 'id', 'status' => 'publish'],
            (new ListPostsQuery(status: 'publish', perPage: 20, fields: 'id'))->toQuery(),
        );
    }
}
