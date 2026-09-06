<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Data\Query;

use JOOservices\WordPress\Sdk\Data\Query\ListCommentsQuery;
use JOOservices\WordPress\Sdk\Data\Query\ListMediaQuery;
use JOOservices\WordPress\Sdk\Data\Query\ListPagesQuery;
use JOOservices\WordPress\Sdk\Data\Query\ListPostsQuery;
use JOOservices\WordPress\Sdk\Data\Query\ListTermsQuery;
use JOOservices\WordPress\Sdk\Data\Query\ListUsersQuery;
use JOOservices\WordPress\Sdk\Data\Query\SearchQuery;
use JOOservices\WordPress\Sdk\Tests\TestCase;

final class AbstractListQueryTest extends TestCase
{
    public function testBaseQueryMapsToWordPressKeys(): void
    {
        $query = new ListPostsQuery(
            page: 2,
            perPage: 10,
            search: 'hello',
            context: 'edit',
            orderby: 'date',
            order: 'desc',
            include: [1, 2],
            exclude: [3],
            fields: 'id,title',
            embed: true,
        );

        self::assertSame([
            'page' => 2,
            'per_page' => 10,
            'search' => 'hello',
            'context' => 'edit',
            'orderby' => 'date',
            'order' => 'desc',
            'include' => [1, 2],
            'exclude' => [3],
            '_fields' => 'id,title',
            '_embed' => 'true',
        ], $query->toQuery());
    }

    public function testNullAndEmptyValuesAreFiltered(): void
    {
        $query = new ListPostsQuery();

        self::assertSame([], $query->toQuery());
    }

    public function testEmbedFalseIsOmitted(): void
    {
        $query = new ListPostsQuery(embed: false);

        self::assertSame([], $query->toQuery());
    }

    public function testPostsQueryExtraParams(): void
    {
        $query = new ListPostsQuery(
            author: [1],
            authorExclude: [2],
            categories: [3],
            categoriesExclude: [9],
            tags: [4],
            tagsExclude: [8],
            status: \JOOservices\WordPress\Sdk\Enums\PostStatus::Publish,
            sticky: true,
            after: '2026-01-01T00:00:00',
            before: '2026-12-31T23:59:59',
            modifiedAfter: '2026-02-01T00:00:00',
            modifiedBefore: '2026-11-01T00:00:00',
            slug: ['hello-world'],
            searchColumns: ['post_title'],
            taxRelation: \JOOservices\WordPress\Sdk\Enums\TaxRelation::And,
            format: 'standard',
            offset: 20,
        );

        self::assertSame([
            'offset' => 20,
            'author' => [1],
            'author_exclude' => [2],
            'categories' => [3],
            'categories_exclude' => [9],
            'tags' => [4],
            'tags_exclude' => [8],
            'status' => 'publish',
            'sticky' => true,
            'after' => '2026-01-01T00:00:00',
            'before' => '2026-12-31T23:59:59',
            'modified_after' => '2026-02-01T00:00:00',
            'modified_before' => '2026-11-01T00:00:00',
            'slug' => ['hello-world'],
            'search_columns' => ['post_title'],
            'tax_relation' => 'AND',
            'format' => 'standard',
        ], $query->toQuery());
    }

    public function testPagesQueryExtraParams(): void
    {
        $query = new ListPagesQuery(parent: 5, parentExclude: [6], status: 'draft');

        self::assertSame([
            'parent' => 5,
            'parent_exclude' => [6],
            'status' => 'draft',
        ], $query->toQuery());
    }

    public function testCommentsQueryExtraParams(): void
    {
        $query = new ListCommentsQuery(post: 42, parent: 1, status: 'approve', type: 'comment');

        self::assertSame([
            'post' => 42,
            'parent' => 1,
            'status' => 'approve',
            'type' => 'comment',
        ], $query->toQuery());
    }

    public function testMediaQueryExtraParams(): void
    {
        $query = new ListMediaQuery(parent: 3, mediaType: 'image', mimeType: 'image/png');

        self::assertSame([
            'parent' => 3,
            'media_type' => 'image',
            'mime_type' => 'image/png',
        ], $query->toQuery());
    }

    public function testTermsQueryExtraParams(): void
    {
        $query = new ListTermsQuery(hideEmpty: true, parent: 2, post: 9, slug: ['news']);

        self::assertSame([
            'hide_empty' => true,
            'parent' => 2,
            'post' => 9,
            'slug' => ['news'],
        ], $query->toQuery());
    }

    public function testUsersQueryExtraParams(): void
    {
        $query = new ListUsersQuery(roles: ['editor'], capabilities: ['edit_posts'], hasPublishedPosts: true);

        self::assertSame([
            'roles' => ['editor'],
            'capabilities' => ['edit_posts'],
            'has_published_posts' => true,
        ], $query->toQuery());
    }

    public function testSearchQueryExtraParams(): void
    {
        $query = new SearchQuery(type: 'post', subtype: 'page', perPage: 5);

        self::assertSame([
            'per_page' => 5,
            'type' => 'post',
            'subtype' => 'page',
        ], $query->toQuery());
    }

    public function testQueryCombinesBaseAndExtraParams(): void
    {
        $query = new ListPostsQuery(status: 'publish', perPage: 20, fields: 'id');

        self::assertSame([
            'per_page' => 20,
            '_fields' => 'id',
            'status' => 'publish',
        ], $query->toQuery());
    }
}
