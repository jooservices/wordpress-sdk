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
        $firstId = $this->faker->numberBetween(1, 1000);
        $secondId = $this->faker->numberBetween(1001, 2000);
        $postA = new Post(id: $firstId);
        $postB = new Post(id: $secondId);

        $collection = new PaginatedCollection([$postA, $postB], total: 20, totalPages: 10);

        self::assertCount(2, $collection);
        self::assertSame(20, $collection->total);
        self::assertSame(10, $collection->totalPages);

        $ids = [];
        foreach ($collection as $post) {
            $ids[] = $post->id;
        }

        self::assertSame([$firstId, $secondId], $ids);
    }

    public function testAllReturnsItems(): void
    {
        $id = $this->faker->numberBetween(1);
        $collection = new PaginatedCollection([new Post(id: $id)], total: 1, totalPages: 1);

        self::assertSame([$id], array_map(static fn(Post $post): int => $post->id, $collection->all()));
        self::assertSame($collection->all(), $collection->items());
    }

    public function testEmptyCollection(): void
    {
        $collection = new PaginatedCollection([], total: 0, totalPages: 0);

        self::assertCount(0, $collection);
        self::assertSame([], $collection->all());
    }
}
