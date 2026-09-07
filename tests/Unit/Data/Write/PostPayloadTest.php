<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Data\Write;

use JOOservices\WordPress\Sdk\Data\Write\PagePayload;
use JOOservices\WordPress\Sdk\Data\Write\PostPayload;
use JOOservices\WordPress\Sdk\Data\Write\TermPayload;
use JOOservices\WordPress\Sdk\Enums\OpenClosed;
use JOOservices\WordPress\Sdk\Enums\PostStatus;
use JOOservices\WordPress\Sdk\Tests\TestCase;

final class PostPayloadTest extends TestCase
{
    public function testToPayloadMapsEnumsAndOmitsEmpty(): void
    {
        $title = $this->faker->sentence(3);
        $payload = new PostPayload(
            title: $title,
            status: PostStatus::Draft,
            commentStatus: OpenClosed::Closed,
            featuredMedia: 12,
        );

        self::assertSame([
            'title' => $title,
            'status' => 'draft',
            'featured_media' => 12,
            'comment_status' => 'closed',
        ], $payload->toPayload());
    }

    public function testPageAndTermPayloadsOmitEmpty(): void
    {
        $title = $this->faker->sentence(2);
        $page = new PagePayload(
            title: $title,
            parent: 3,
            menuOrder: 1,
        );
        self::assertSame(['title' => $title, 'parent' => 3, 'menu_order' => 1], $page->toPayload());

        $name = $this->faker->word();
        $term = new TermPayload(name: $name, parent: 2);
        self::assertSame(['name' => $name, 'parent' => 2], $term->toPayload());
    }

    public function testEmptyCategoriesAndTagsArePreserved(): void
    {
        $title = $this->faker->sentence(2);
        $payload = new PostPayload(title: $title, categories: [], tags: []);

        self::assertSame([
            'title' => $title,
            'categories' => [],
            'tags' => [],
        ], $payload->toPayload());
    }

    public function testNullCategoriesAndTagsAreOmitted(): void
    {
        $title = $this->faker->sentence(2);
        $payload = new PostPayload(title: $title);

        self::assertSame(['title' => $title], $payload->toPayload());
    }
}
