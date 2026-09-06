<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Pagination;

use JOOservices\WordPress\Sdk\Data\Post;
use JOOservices\WordPress\Sdk\Pagination\PaginatedCollection;
use JOOservices\WordPress\Sdk\Tests\TestCase;

final class PaginatedCollectionTest extends TestCase
{
    public function testIteratesAndCountsItems(): void
    {
        $postA = new Post(id: 1);
        $postB = new Post(id: 2);

        $collection = new PaginatedCollection([$postA, $postB], total: 20, totalPages: 10);

        self::assertCount(2, $collection);
        self::assertSame(20, $collection->total);
        self::assertSame(10, $collection->totalPages);

        $ids = [];
        foreach ($collection as $post) {
            $ids[] = $post->id;
        }

        self::assertSame([1, 2], $ids);
    }

    public function testAllReturnsItems(): void
    {
        $collection = new PaginatedCollection([new Post(id: 3)], total: 1, totalPages: 1);

        self::assertSame([3], array_map(static fn(Post $post): int => $post->id, $collection->all()));
        self::assertSame($collection->all(), $collection->items());
    }

    public function testEmptyCollection(): void
    {
        $collection = new PaginatedCollection([], total: 0, totalPages: 0);

        self::assertCount(0, $collection);
        self::assertSame([], $collection->all());
    }
}
