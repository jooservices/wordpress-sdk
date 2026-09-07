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

final class UserTest extends TestCase
{
    public function testPostHydratesFromWordPressPayload(): void
    {
        $title = $this->faker->sentence(3);
        $content = sprintf('<p>%s</p>', $this->faker->sentence());
        $excerpt = sprintf('<p>%s</p>', $this->faker->sentence());
        $guid = $this->faker->url();
        $link = $this->faker->url();
        $slug = $this->faker->slug();
        $metaValue = (string) $this->faker->randomNumber();
        $payload = json_decode(json_encode([
            'id' => '42',
            'date' => '2026-08-29T10:00:00',
            'date_gmt' => '2026-08-29T10:00:00',
            'guid' => ['rendered' => $guid],
            'modified' => '2026-08-29T11:00:00',
            'modified_gmt' => '2026-08-29T11:00:00',
            'slug' => $slug,
            'status' => 'publish',
            'type' => 'post',
            'link' => $link,
            'title' => ['rendered' => $title, 'raw' => $title],
            'content' => ['rendered' => $content, 'protected' => false],
            'excerpt' => ['rendered' => $excerpt, 'protected' => false],
            'author' => '1',
            'featured_media' => '7',
            'comment_status' => 'open',
            'ping_status' => 'closed',
            'sticky' => true,
            'template' => false,
            'format' => 'standard',
            'meta' => ['_edit_lock' => $metaValue],
            'categories' => ['3', 4],
            'tags' => [5],
        ], JSON_THROW_ON_ERROR), true);
        /** @var array<string, mixed> $payload */
        $post = Post::from($payload);

        self::assertSame(42, $post->id);
        self::assertSame(7, $post->featured_media);
        self::assertSame($title, $post->title?->rendered);
        self::assertSame($title, $post->title->raw);
        self::assertSame($content, $post->content?->rendered);
        self::assertFalse($post->content->protected);
        self::assertInstanceOf(RenderedContent::class, $post->guid);
        self::assertTrue($post->sticky);
        self::assertSame('', $post->template);
        self::assertSame([3, 4], $post->categories);
        self::assertSame([5], $post->tags);
        self::assertSame(['_edit_lock' => $metaValue], $post->meta);
    }

    public function testPostDefaultsWhenPayloadIsEmpty(): void
    {
        $post = new Post();

        self::assertSame(0, $post->id);
        self::assertNull($post->featured_media);
        self::assertSame('', $post->slug);
        self::assertNull($post->title);
        self::assertSame([], $post->categories);
        self::assertFalse($post->sticky);
    }

    public function testPostAcceptsNullFeaturedMedia(): void
    {
        $post = Post::from(['id' => 1, 'featured_media' => null]);

        self::assertNull($post->featured_media);
    }

    public function testPostIgnoresUnknownKeys(): void
    {
        $post = Post::from([
            'id' => 1,
            '_links' => ['self' => [['href' => $this->faker->url()]]],
            'junk' => $this->faker->word(),
        ]);

        self::assertSame(1, $post->id);
    }

    public function testPageExtendsPost(): void
    {
        $title = $this->faker->sentence(2);
        $page = Page::from(['id' => '8', 'type' => 'page', 'title' => ['rendered' => $title]]);

        self::assertSame(8, $page->id);
        self::assertSame($title, $page->title?->rendered);
    }

    public function testMediaHydrates(): void
    {
        $title = $this->faker->sentence(2);
        $caption = sprintf('<p>%s</p>', $this->faker->sentence());
        $description = sprintf('<p>%s</p>', $this->faker->sentence());
        $altText = $this->faker->words(2, true);
        $sourceUrl = $this->faker->url();
        $media = Media::from([
            'id' => '3',
            'title' => ['rendered' => $title],
            'caption' => ['rendered' => $caption],
            'description' => ['rendered' => $description],
            'alt_text' => $altText,
            'media_type' => 'image',
            'mime_type' => 'image/png',
            'media_details' => ['width' => 800],
            'author' => '1',
            'source_url' => $sourceUrl,
        ]);

        self::assertSame(3, $media->id);
        self::assertSame($title, $media->title?->rendered);
        self::assertSame($altText, $media->alt_text);
        self::assertSame(['width' => 800], $media->media_details);
        self::assertSame($sourceUrl, $media->source_url);
    }

    public function testCommentHydrates(): void
    {
        $authorName = $this->faker->name();
        $content = sprintf('<p>%s</p>', $this->faker->sentence());
        $smallAvatar = $this->faker->imageUrl();
        $largeAvatar = $this->faker->imageUrl();
        $comment = Comment::from([
            'id' => '1',
            'post' => '42',
            'parent' => '0',
            'author' => '2',
            'author_name' => $authorName,
            'author_url' => '',
            'content' => ['rendered' => $content],
            'status' => 'approve',
            'type' => 'comment',
            'author_avatar_urls' => ['24' => $smallAvatar, '96' => $largeAvatar],
        ]);

        self::assertSame(1, $comment->id);
        self::assertSame(42, $comment->post);
        self::assertSame($authorName, $comment->author_name);
        self::assertSame($content, $comment->content?->rendered);
        self::assertSame($largeAvatar, $comment->author_avatar_urls['96'] ?? null);
    }

    public function testUserHydrates(): void
    {
        $name = $this->faker->name();
        $slug = $this->faker->slug();
        $link = $this->faker->url();
        $avatarUrl = $this->faker->imageUrl();
        $username = $this->faker->userName();
        $user = User::from([
            'id' => '9',
            'name' => $name,
            'slug' => $slug,
            'link' => $link,
            'avatar_urls' => ['96' => $avatarUrl],
            'meta' => ['nickname' => $name],
            'username' => $username,
        ]);

        self::assertSame(9, $user->id);
        self::assertSame($name, $user->name);
        self::assertSame($username, $user->username);
        self::assertSame(['96' => $avatarUrl], $user->avatar_urls);
    }

    public function testUserHydratesEditContextFields(): void
    {
        $email = $this->faker->email();
        $firstName = $this->faker->firstName();
        $lastName = $this->faker->lastName();
        $user = User::from([
            'id' => 2,
            'name' => $this->faker->name(),
            'slug' => $this->faker->slug(),
            'email' => $email,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'nickname' => $this->faker->userName(),
            'locale' => 'en_US',
            'registered_date' => '2026-01-01T00:00:00',
            'roles' => ['editor'],
            'capabilities' => ['edit_posts' => true],
            'extra_capabilities' => ['administrator' => false],
        ]);

        self::assertSame($email, $user->email);
        self::assertSame($firstName, $user->first_name);
        self::assertSame($lastName, $user->last_name);
        self::assertSame(['editor'], $user->roles);
        self::assertSame(['edit_posts' => true], $user->capabilities);
        self::assertSame(['administrator' => false], $user->extra_capabilities);
    }

    public function testUserWithoutUsername(): void
    {
        $user = User::from(['id' => 1, 'slug' => $this->faker->slug()]);

        self::assertNull($user->username);
    }

    public function testTermHydrates(): void
    {
        $description = $this->faker->sentence();
        $name = $this->faker->word();
        $slug = $this->faker->slug();
        $term = Term::from([
            'id' => '5',
            'count' => '3',
            'description' => $description,
            'name' => $name,
            'slug' => $slug,
            'taxonomy' => 'category',
            'parent' => '0',
        ]);

        self::assertSame(5, $term->id);
        self::assertSame(3, $term->count);
        self::assertSame($name, $term->name);
        self::assertSame('category', $term->taxonomy);
    }

    public function testTaxonomyHydrates(): void
    {
        $name = $this->faker->word();
        $taxonomy = Taxonomy::from([
            'slug' => 'category',
            'name' => $name,
            'types' => ['post', 'page'],
            'rest_base' => 'categories',
            'hierarchical' => true,
            'rest_namespace' => 'wp/v2',
            'labels' => ['name' => $name],
        ]);

        self::assertSame('category', $taxonomy->slug);
        self::assertSame(['post', 'page'], $taxonomy->types);
        self::assertTrue($taxonomy->hierarchical);
    }

    public function testPostTypeHydrates(): void
    {
        $name = $this->faker->word();
        $postType = PostType::from([
            'slug' => 'post',
            'name' => $name,
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
        $name = $this->faker->word();
        $status = Status::from([
            'name' => $name,
            'slug' => 'publish',
            'public' => true,
            'queryable' => true,
            'show_in_list' => true,
        ]);

        self::assertSame($name, $status->name);
        self::assertSame('publish', $status->slug);
        self::assertTrue($status->public);
        self::assertFalse($status->protected);
    }

    public function testSearchResultHydrates(): void
    {
        $title = $this->faker->sentence(2);
        $result = SearchResult::from([
            'id' => '12',
            'title' => $title,
            'url' => $this->faker->url(),
            'type' => 'post',
            'subtype' => 'post',
        ]);

        self::assertSame(12, $result->id);
        self::assertSame($title, $result->title);
        self::assertSame('post', $result->type);
    }

    public function testSettingsWrapValuesAndLookUpKeys(): void
    {
        $title = $this->faker->sentence(2);
        $fallback = $this->faker->word();
        $settings = Settings::from(['title' => $title, 'users_can_register' => 0]);

        self::assertSame($title, $settings->get('title'));
        self::assertSame(0, $settings->get('users_can_register'));
        self::assertSame($fallback, $settings->get('missing', $fallback));
        self::assertNull($settings->get('missing'));
    }

    public function testSettingsPreservesArrayValuedSettingNamedValues(): void
    {
        $title = $this->faker->sentence(2);
        $settings = Settings::from(['values' => ['title' => $title]]);

        self::assertSame(['title' => $title], $settings->get('values'));
        self::assertSame(['values' => ['title' => $title]], $settings->toArray());
    }

    public function testApplicationPasswordHydrates(): void
    {
        $uuid = $this->faker->uuid();
        $name = $this->faker->word();
        $password = ApplicationPassword::from([
            'uuid' => $uuid,
            'app_id' => 5,
            'name' => $name,
            'created' => '2026-08-29T10:00:00',
            'last_used' => '2026-08-29T11:00:00',
            'last_ip' => '127.0.0.1',
        ]);

        self::assertSame($uuid, $password->uuid);
        self::assertSame($name, $password->name);
        self::assertNull($password->password);
    }

    public function testApplicationPasswordCarriesGeneratedSecretOnCreate(): void
    {
        $generatedPassword = $this->faker->password();
        $password = ApplicationPassword::from([
            'uuid' => $this->faker->uuid(),
            'name' => $this->faker->word(),
            'password' => $generatedPassword,
        ]);

        self::assertSame($generatedPassword, $password->password);
    }

    public function testDtoSerializationRoundTrip(): void
    {
        $title = $this->faker->sentence(2);
        $post = Post::from(['id' => 1, 'title' => ['rendered' => $title], 'sticky' => true]);

        $array = $post->toArray();

        self::assertSame(1, $array['id']);
        $serializedTitle = $array['title'] ?? [];
        /** @var array<string, mixed> $serializedTitle */
        self::assertSame($title, $serializedTitle['rendered'] ?? null);
        self::assertTrue($array['sticky']);
    }
}
