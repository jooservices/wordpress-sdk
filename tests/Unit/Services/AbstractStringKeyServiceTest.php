<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Services;

use JOOservices\Client\Testing\TestResponse;
use JOOservices\Client\Testing\TestResponseSequence;
use JOOservices\WordPress\Sdk\Data\PostType;
use JOOservices\WordPress\Sdk\Data\Status;
use JOOservices\WordPress\Sdk\Data\Taxonomy;
use JOOservices\WordPress\Sdk\Tests\TestCase;
use JOOservices\WordPress\Sdk\WordPressService;

final class AbstractStringKeyServiceTest extends TestCase
{
    private WordPressService $wordPress;

    protected function setUp(): void
    {
        parent::setUp();
        $this->wordPress = $this->wordPress();
    }

    public function testTaxonomiesGetBySlug(): void
    {
        $name = $this->faker->word();
        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::json(['slug' => 'category', 'name' => $name]));
        $this->httpFakes()->respond('GET', '*wp/v2/taxonomies/category*', $sequence);

        $taxonomy = $this->wordPress->taxonomies()->get('category');

        self::assertInstanceOf(Taxonomy::class, $taxonomy);
        self::assertSame($name, $taxonomy->name);
        self::assertSame('/wp-json/wp/v2/taxonomies/category', $this->lastRequest()->getUri()->getPath());
    }

    public function testTaxonomiesGetEncodesSpecialCharactersInSlug(): void
    {
        $name = $this->faker->word();
        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::json(['slug' => 'a b', 'name' => $name]));
        $this->httpFakes()->respond('GET', '*wp/v2/taxonomies/a%20b*', $sequence);

        $taxonomy = $this->wordPress->taxonomies()->get('a b');

        self::assertSame($name, $taxonomy->name);
        self::assertSame('/wp-json/wp/v2/taxonomies/a%20b', $this->lastRequest()->getUri()->getPath());
    }

    public function testTaxonomiesListHandlesAssocPayload(): void
    {
        $categoryName = $this->faker->word();
        $tagName = $this->faker->word();
        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::make(200, [], json_encode([
            'category' => ['slug' => 'category', 'name' => $categoryName],
            'post_tag' => ['slug' => 'post_tag', 'name' => $tagName],
        ], JSON_THROW_ON_ERROR)));
        $this->httpFakes()->respond('GET', '*wp/v2/taxonomies*', $sequence);

        $taxonomies = $this->wordPress->taxonomies()->list();

        self::assertCount(2, $taxonomies);
        self::assertSame($categoryName, $taxonomies->all()[0]->name);
    }

    public function testPostTypesGetBySlug(): void
    {
        $name = $this->faker->word();
        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::json(['slug' => 'post', 'name' => $name]));
        $this->httpFakes()->respond('GET', '*wp/v2/types/post*', $sequence);

        $postType = $this->wordPress->postTypes()->get('post');

        self::assertInstanceOf(PostType::class, $postType);
        self::assertSame($name, $postType->name);
        self::assertSame('/wp-json/wp/v2/types/post', $this->lastRequest()->getUri()->getPath());
    }

    public function testPostTypesListHandlesAssocPayload(): void
    {
        $name = $this->faker->word();
        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::make(200, [], json_encode([
            'post' => ['slug' => 'post', 'name' => $name],
        ], JSON_THROW_ON_ERROR)));
        $this->httpFakes()->respond('GET', '*wp/v2/types*', $sequence);

        $types = $this->wordPress->postTypes()->list();

        self::assertCount(1, $types);
        self::assertSame($name, $types->all()[0]->name);
    }

    public function testStatusesGetBySlug(): void
    {
        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::json(['name' => 'Publish', 'slug' => 'publish', 'public' => true]));
        $this->httpFakes()->respond('GET', '*wp/v2/statuses/publish*', $sequence);

        $status = $this->wordPress->statuses()->get('publish');

        self::assertInstanceOf(Status::class, $status);
        self::assertSame('publish', $status->slug);
        self::assertTrue($status->public);
    }

    public function testStatusesListHandlesAssocPayload(): void
    {
        $publishName = $this->faker->word();
        $draftName = $this->faker->word();
        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::make(200, [], json_encode([
            'publish' => ['name' => $publishName, 'public' => true],
            'draft' => ['name' => $draftName],
        ], JSON_THROW_ON_ERROR)));
        $this->httpFakes()->respond('GET', '*wp/v2/statuses*', $sequence);

        $statuses = $this->wordPress->statuses()->list();

        self::assertCount(2, $statuses);
        self::assertTrue($statuses->all()[0]->public);
    }

    public function testSchemaServicesStreamHelpers(): void
    {
        $postName = $this->faker->word();
        $pageName = $this->faker->word();
        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::make(200, [], json_encode([
            'post' => ['slug' => 'post', 'name' => $postName],
            'page' => ['slug' => 'page', 'name' => $pageName],
        ], JSON_THROW_ON_ERROR)));
        $this->httpFakes()->respond('GET', '*wp/v2/types*', $sequence);

        $ids = [];
        $this->wordPress->postTypes()->each(function (PostType $type) use (&$ids) {
            $ids[] = $type->slug;
        });

        self::assertSame(['post', 'page'], $ids);
    }
}
