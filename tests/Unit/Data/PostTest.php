<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Data;

use JOOservices\WordPress\Sdk\Data\Post;
use JOOservices\WordPress\Sdk\Data\RenderedContent;
use JOOservices\WordPress\Sdk\Tests\TestCase;

final class PostTest extends TestCase
{
    public function testHydratesCompleteWordPressPayload(): void
    {
        $title = $this->faker->sentence(3);
        $content = sprintf('<p>%s</p>', $this->faker->sentence());
        $metaValue = (string) $this->faker->randomNumber();
        $payload = json_decode(json_encode([
            'id' => '42', 'date' => '2026-08-29T10:00:00', 'date_gmt' => '2026-08-29T10:00:00',
            'guid' => ['rendered' => $this->faker->url()], 'modified' => '2026-08-29T11:00:00',
            'modified_gmt' => '2026-08-29T11:00:00', 'slug' => $this->faker->slug(),
            'status' => 'publish', 'type' => 'post', 'link' => $this->faker->url(),
            'title' => ['rendered' => $title, 'raw' => $title],
            'content' => ['rendered' => $content, 'protected' => false],
            'excerpt' => ['rendered' => sprintf('<p>%s</p>', $this->faker->sentence()), 'protected' => false],
            'author' => '1', 'featured_media' => '7', 'comment_status' => 'open', 'ping_status' => 'closed',
            'sticky' => true, 'template' => false, 'format' => 'standard', 'meta' => ['_edit_lock' => $metaValue],
            'categories' => ['3', 4], 'tags' => [5],
        ], JSON_THROW_ON_ERROR), true);
        /** @var array<string, mixed> $payload */
        $post = Post::from($payload);

        self::assertSame(42, $post->id);
        self::assertSame(7, $post->featured_media);
        self::assertSame($title, $post->title?->rendered);
        self::assertSame($content, $post->content?->rendered);
        self::assertInstanceOf(RenderedContent::class, $post->guid);
        self::assertTrue($post->sticky);
        self::assertSame('', $post->template);
        self::assertSame([3, 4], $post->categories);
        self::assertSame([5], $post->tags);
        self::assertSame(['_edit_lock' => $metaValue], $post->meta);
    }

    public function testUsesDefaultsForEmptyPayload(): void
    {
        $post = new Post();
        self::assertSame(0, $post->id);
        self::assertNull($post->featured_media);
        self::assertSame('', $post->slug);
        self::assertNull($post->title);
        self::assertSame([], $post->categories);
        self::assertFalse($post->sticky);
    }

    public function testAcceptsNullFeaturedMedia(): void
    {
        self::assertNull(Post::from(['id' => 1, 'featured_media' => null])->featured_media);
    }

    public function testIgnoresUnknownKeys(): void
    {
        $post = Post::from(['id' => 1, '_links' => ['self' => [['href' => $this->faker->url()]]], 'junk' => $this->faker->word()]);
        self::assertSame(1, $post->id);
    }

    public function testSerializesToArray(): void
    {
        $title = $this->faker->sentence(2);
        $array = Post::from(['id' => 1, 'title' => ['rendered' => $title], 'sticky' => true])->toArray();
        self::assertSame(1, $array['id']);
        $serializedTitle = $array['title'] ?? [];
        /** @var array<string, mixed> $serializedTitle */
        self::assertSame($title, $serializedTitle['rendered'] ?? null);
        self::assertTrue($array['sticky']);
    }

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
}
