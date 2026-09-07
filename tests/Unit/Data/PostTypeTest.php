<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Data;

use JOOservices\WordPress\Sdk\Data\PostType;
use JOOservices\WordPress\Sdk\Tests\TestCase;

final class PostTypeTest extends TestCase
{
    public function testHydrates(): void
    {
        $postType = PostType::from([
            'slug' => 'post', 'name' => $this->faker->word(), 'hierarchical' => false,
            'viewable' => true, 'supports' => ['title', 'editor'], 'taxonomies' => ['category', 'post_tag'],
        ]);

        self::assertSame('post', $postType->slug);
        self::assertSame(['title', 'editor'], $postType->supports);
        self::assertTrue($postType->viewable);
    }
}
