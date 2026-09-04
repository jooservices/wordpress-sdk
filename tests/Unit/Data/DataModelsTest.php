<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Data;

use JOOservices\WordPress\Sdk\Data\ApplicationPassword;
use JOOservices\WordPress\Sdk\Data\Comment;
use JOOservices\WordPress\Sdk\Data\Media;
use JOOservices\WordPress\Sdk\Data\Page;
use JOOservices\WordPress\Sdk\Data\Post;
use JOOservices\WordPress\Sdk\Data\PostType;
use JOOservices\WordPress\Sdk\Data\RenderedContent;
use JOOservices\WordPress\Sdk\Data\SearchResult;
use JOOservices\WordPress\Sdk\Data\Settings;
use JOOservices\WordPress\Sdk\Data\Status;
use JOOservices\WordPress\Sdk\Data\Taxonomy;
use JOOservices\WordPress\Sdk\Data\Term;
use JOOservices\WordPress\Sdk\Data\User;
use JOOservices\WordPress\Sdk\Tests\TestCase;

final class DataModelsTest extends TestCase
{
    public function testPostHydratesFromWordPressPayload(): void
    {
        $payload = json_decode(json_encode([
            'id' => '42',
            'date' => '2026-08-29T10:00:00',
            'date_gmt' => '2026-08-29T10:00:00',
            'guid' => ['rendered' => 'https://example.com/?p=42'],
            'modified' => '2026-08-29T11:00:00',
            'modified_gmt' => '2026-08-29T11:00:00',
            'slug' => 'hello-world',
            'status' => 'publish',
            'type' => 'post',
            'link' => 'https://example.com/hello-world/',
            'title' => ['rendered' => 'Hello World', 'raw' => 'Hello World'],
            'content' => ['rendered' => '<p>Content</p>', 'protected' => false],
            'excerpt' => ['rendered' => '<p>Excerpt</p>', 'protected' => false],
            'author' => '1',
            'featured_media' => '7',
            'comment_status' => 'open',
            'ping_status' => 'closed',
            'sticky' => true,
            'template' => false,
            'format' => 'standard',
            'meta' => ['_edit_lock' => '123'],
            'categories' => ['3', 4],
            'tags' => [5],
        ], JSON_THROW_ON_ERROR), true);
        /** @var array<string, mixed> $payload */
        $post = Post::from($payload);

        self::assertSame(42, $post->id);
        self::assertSame('Hello World', $post->title?->rendered);
        self::assertSame('Hello World', $post->title->raw);
        self::assertSame('<p>Content</p>', $post->content?->rendered);
        self::assertFalse($post->content->protected);
        self::assertInstanceOf(RenderedContent::class, $post->guid);
        self::assertTrue($post->sticky);
        self::assertSame('', $post->template);
        self::assertSame([3, 4], $post->categories);
        self::assertSame([5], $post->tags);
        self::assertSame(['_edit_lock' => '123'], $post->meta);
    }

    public function testPostDefaultsWhenPayloadIsEmpty(): void
    {
        $post = new Post();

        self::assertSame(0, $post->id);
        self::assertSame('', $post->slug);
        self::assertNull($post->title);
        self::assertSame([], $post->categories);
        self::assertFalse($post->sticky);
    }

    public function testPostIgnoresUnknownKeys(): void
    {
        $post = Post::from(['id' => 1, '_links' => ['self' => [['href' => '/x']]], 'junk' => 'x']);

        self::assertSame(1, $post->id);
    }

    public function testPageExtendsPost(): void
    {
        $page = Page::from(['id' => '8', 'type' => 'page', 'title' => ['rendered' => 'About']]);

        self::assertSame(8, $page->id);
        self::assertSame('About', $page->title?->rendered);
    }

    public function testMediaHydrates(): void
    {
        $media = Media::from([
            'id' => '3',
            'title' => ['rendered' => 'Image'],
            'caption' => ['rendered' => '<p>Cap</p>'],
            'description' => ['rendered' => '<p>Desc</p>'],
            'alt_text' => 'Alt',
            'media_type' => 'image',
            'mime_type' => 'image/png',
            'media_details' => ['width' => 800],
            'author' => '1',
            'source_url' => 'https://example.com/wp-content/uploads/2026/08/a.png',
        ]);

        self::assertSame(3, $media->id);
        self::assertSame('Image', $media->title?->rendered);
        self::assertSame('Alt', $media->alt_text);
        self::assertSame(['width' => 800], $media->media_details);
        self::assertSame('https://example.com/wp-content/uploads/2026/08/a.png', $media->source_url);
    }

    public function testCommentHydrates(): void
    {
        $comment = Comment::from([
            'id' => '1',
            'post' => '42',
            'parent' => '0',
            'author' => '2',
            'author_name' => 'Jane',
            'author_url' => '',
            'content' => ['rendered' => '<p>Hi</p>'],
            'status' => 'approve',
            'type' => 'comment',
            'author_avatar_urls' => ['24' => 'https://example.com/a24.png', '96' => 'https://example.com/a96.png'],
        ]);

        self::assertSame(1, $comment->id);
        self::assertSame(42, $comment->post);
        self::assertSame('Jane', $comment->author_name);
        self::assertSame('<p>Hi</p>', $comment->content?->rendered);
        self::assertSame('https://example.com/a96.png', $comment->author_avatar_urls['96'] ?? null);
    }

    public function testUserHydrates(): void
    {
        $user = User::from([
            'id' => '9',
            'name' => 'Admin',
            'slug' => 'admin',
            'link' => 'https://example.com/author/admin/',
            'avatar_urls' => ['96' => 'https://example.com/avatar.png'],
            'meta' => ['nickname' => 'Admin'],
            'username' => 'admin_login',
        ]);

        self::assertSame(9, $user->id);
        self::assertSame('Admin', $user->name);
        self::assertSame('admin_login', $user->username);
        self::assertSame(['96' => 'https://example.com/avatar.png'], $user->avatar_urls);
    }

    public function testUserWithoutUsername(): void
    {
        $user = User::from(['id' => 1, 'slug' => 'admin']);

        self::assertNull($user->username);
    }

    public function testTermHydrates(): void
    {
        $term = Term::from([
            'id' => '5',
            'count' => '3',
            'description' => 'Desc',
            'name' => 'News',
            'slug' => 'news',
            'taxonomy' => 'category',
            'parent' => '0',
        ]);

        self::assertSame(5, $term->id);
        self::assertSame(3, $term->count);
        self::assertSame('News', $term->name);
        self::assertSame('category', $term->taxonomy);
    }

    public function testTaxonomyHydrates(): void
    {
        $taxonomy = Taxonomy::from([
            'slug' => 'category',
            'name' => 'Categories',
            'types' => ['post', 'page'],
            'rest_base' => 'categories',
            'hierarchical' => true,
            'rest_namespace' => 'wp/v2',
            'labels' => ['name' => 'Categories'],
        ]);

        self::assertSame('category', $taxonomy->slug);
        self::assertSame(['post', 'page'], $taxonomy->types);
        self::assertTrue($taxonomy->hierarchical);
    }

    public function testPostTypeHydrates(): void
    {
        $postType = PostType::from([
            'slug' => 'post',
            'name' => 'Posts',
            'hierarchical' => false,
            'viewable' => true,
            'supports' => ['title', 'editor'],
            'taxonomies' => ['category', 'post_tag'],
        ]);

        self::assertSame('post', $postType->slug);
        self::assertSame(['title', 'editor'], $postType->supports);
        self::assertTrue($postType->viewable);
    }

    public function testStatusHydrates(): void
    {
        $status = Status::from([
            'name' => 'Publish',
            'public' => true,
            'queryable' => true,
            'show_in_list' => true,
        ]);

        self::assertSame('Publish', $status->name);
        self::assertTrue($status->public);
        self::assertFalse($status->protected);
    }

    public function testSearchResultHydrates(): void
    {
        $result = SearchResult::from([
            'id' => '12',
            'title' => 'Found',
            'url' => 'https://example.com/found/',
            'type' => 'post',
            'subtype' => 'post',
        ]);

        self::assertSame(12, $result->id);
        self::assertSame('Found', $result->title);
        self::assertSame('post', $result->type);
    }

    public function testSettingsWrapValuesAndLookUpKeys(): void
    {
        $settings = Settings::from(['title' => 'My Site', 'users_can_register' => 0]);

        self::assertSame('My Site', $settings->get('title'));
        self::assertSame(0, $settings->get('users_can_register'));
        self::assertSame('fallback', $settings->get('missing', 'fallback'));
        self::assertNull($settings->get('missing'));
    }

    public function testApplicationPasswordHydrates(): void
    {
        $password = ApplicationPassword::from([
            'uuid' => 'abc-123',
            'app_id' => 5,
            'name' => 'Worker',
            'created' => '2026-08-29T10:00:00',
            'last_used' => '2026-08-29T11:00:00',
            'last_ip' => '127.0.0.1',
        ]);

        self::assertSame('abc-123', $password->uuid);
        self::assertSame('Worker', $password->name);
        self::assertNull($password->password);
    }

    public function testApplicationPasswordCarriesGeneratedSecretOnCreate(): void
    {
        $password = ApplicationPassword::from([
            'uuid' => 'abc',
            'name' => 'Worker',
            'password' => 'abcd efgh ijkl',
        ]);

        self::assertSame('abcd efgh ijkl', $password->password);
    }

    public function testDtoSerializationRoundTrip(): void
    {
        $post = Post::from(['id' => 1, 'title' => ['rendered' => 'T'], 'sticky' => true]);

        $array = $post->toArray();

        self::assertSame(1, $array['id']);
        $title = $array['title'] ?? [];
        /** @var array<string, mixed> $title */
        self::assertSame('T', $title['rendered'] ?? null);
        self::assertTrue($array['sticky']);
    }
}
