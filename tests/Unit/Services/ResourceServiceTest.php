<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Services;

use JOOservices\Client\Testing\TestResponse;
use JOOservices\Client\Testing\TestResponseSequence;
use JOOservices\WordPress\Sdk\Data\Post;
use JOOservices\WordPress\Sdk\Data\Term;
use JOOservices\WordPress\Sdk\Data\Write\PostPayload;
use JOOservices\WordPress\Sdk\Enums\PostStatus;
use JOOservices\WordPress\Sdk\Tests\TestCase;

final class ResourceServiceTest extends TestCase
{
    public function testResourceBareSlugUsesWpV2Prefix(): void
    {
        $title = $this->faker->sentence(3);
        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::json(['id' => 4, 'title' => ['rendered' => $title]]));
        $this->httpFakes()->respond('GET', '*wp/v2/product/4*', $sequence);

        $post = $this->wordPress()->resource('product')->get(4);

        self::assertInstanceOf(Post::class, $post);
        self::assertSame($title, $post->title?->rendered);
        self::assertSame('/wp-json/wp/v2/product/4', $this->lastRequest()->getUri()->getPath());
    }

    public function testResourceKeepsNamespacedPath(): void
    {
        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::json(['id' => 1, 'status' => 'publish'], 201));
        $this->httpFakes()->respond('POST', '*wp/v2/book*', $sequence);

        $created = $this->wordPress()->resource('wp/v2/book')->create(new PostPayload(
            title: $this->faker->sentence(2),
            status: PostStatus::Publish,
        ));

        self::assertSame(1, $created->id);
        self::assertSame('/wp-json/wp/v2/book', $this->lastRequest()->getUri()->getPath());
    }

    public function testTermsBareSlugUsesWpV2Prefix(): void
    {
        $name = $this->faker->word();
        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::json(['id' => 9, 'name' => $name, 'taxonomy' => 'product_cat']));
        $this->httpFakes()->respond('GET', '*wp/v2/product_cat/9*', $sequence);

        $term = $this->wordPress()->terms('product_cat')->get(9);

        self::assertInstanceOf(Term::class, $term);
        self::assertSame($name, $term->name);
        self::assertSame('/wp-json/wp/v2/product_cat/9', $this->lastRequest()->getUri()->getPath());
    }

    public function testResourceCachesPerPath(): void
    {
        $wordPress = $this->wordPress();

        self::assertSame($wordPress->resource('product'), $wordPress->resource('product'));
        self::assertNotSame($wordPress->resource('product'), $wordPress->resource('book'));
    }

    public function testResourceRevisionsAndTermsCreate(): void
    {
        $revisions = new TestResponseSequence();
        $revisions->push(TestResponse::json([['id' => 2]]));
        $this->httpFakes()->respond('GET', '*wp/v2/product/4/revisions*', $revisions);

        self::assertSame([['id' => 2]], $this->wordPress()->resource('product')->revisions(4)->list());

        $autosaves = new TestResponseSequence();
        $autosaves->push(TestResponse::json([['id' => 3]]));
        $this->httpFakes()->respond('GET', '*wp/v2/product/4/autosaves*', $autosaves);

        self::assertSame([['id' => 3]], $this->wordPress()->resource('product')->autosaves(4)->list());

        $name = $this->faker->word();
        $create = new TestResponseSequence();
        $create->push(TestResponse::json(['id' => 11, 'name' => $name], 201));
        $this->httpFakes()->respond('POST', '*wp/v2/product_cat*', $create);

        $term = $this->wordPress()->terms('product_cat')->create(['name' => $name]);

        self::assertSame(11, $term->id);
        self::assertSame($name, $term->name);
    }
}
