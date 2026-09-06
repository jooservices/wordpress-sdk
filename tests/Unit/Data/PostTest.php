<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Data;

use JOOservices\WordPress\Sdk\Data\Page;
use JOOservices\WordPress\Sdk\Data\Post;
use JOOservices\WordPress\Sdk\Tests\TestCase;

final class PostTest extends TestCase
{
    public function testHydratesFromWordPressPayloadIncludingEditFields(): void
    {
        $post = Post::from([
            'id' => '42',
            'title' => ['rendered' => $this->faker->sentence(2), 'raw' => 'Raw'],
            'featured_media' => '7',
            'categories' => ['3', 4],
            'template' => false,
            'password' => 'secret',
            'permalink_template' => 'https://example.test/%postname%/',
            'generated_slug' => 'hello',
            'class_list' => ['post', 'type-post'],
            'parent' => 9,
            'menu_order' => 3,
            '_links' => ['self' => [['href' => '/x']]],
        ]);

        self::assertSame(42, $post->id);
        self::assertSame(7, $post->featured_media);
        self::assertSame('Raw', $post->title?->raw);
        self::assertSame('', $post->template);
        self::assertSame('secret', $post->password);
        self::assertSame(['post', 'type-post'], $post->class_list);
        self::assertSame(9, $post->parent);
        self::assertSame(3, $post->menu_order);
        self::assertNotNull($post->title);
    }

    public function testPageHydratesHierarchicalFields(): void
    {
        $page = Page::from([
            'id' => 8,
            'type' => 'page',
            'parent' => 3,
            'menu_order' => 2,
            'title' => ['rendered' => $this->faker->words(2, true)],
        ]);

        self::assertSame(8, $page->id);
        self::assertSame(3, $page->parent);
        self::assertSame(2, $page->menu_order);
    }
}
