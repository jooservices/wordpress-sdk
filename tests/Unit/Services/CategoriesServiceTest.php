<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Services;

use JOOservices\Client\Testing\TestResponse;
use JOOservices\Client\Testing\TestResponseSequence;
use JOOservices\WordPress\Sdk\Data\Term;
use JOOservices\WordPress\Sdk\Tests\TestCase;
use JOOservices\WordPress\Sdk\WordPressService;

final class CategoriesServiceTest extends TestCase
{
    private WordPressService $wordPress;

    protected function setUp(): void
    {
        parent::setUp();
        $this->wordPress = $this->wordPress();
    }

    public function testCategoriesRouteAndHydrateTerms(): void
    {
        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::json(['id' => 5, 'name' => 'News', 'taxonomy' => 'category']));
        $this->httpFakes()->respond('GET', '*wp/v2/categories/5*', $sequence);

        $category = $this->wordPress->categories()->get(5);

        self::assertInstanceOf(Term::class, $category);
        self::assertSame('News', $category->name);
        self::assertSame('/wp-json/wp/v2/categories/5', $this->lastRequest()->getUri()->getPath());
    }

    public function testTagsRouteToTagsEndpoint(): void
    {
        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::make(200, [
            'X-WP-Total' => '1',
            'X-WP-TotalPages' => '1',
        ], json_encode([
            ['id' => 8, 'name' => 'PHP', 'taxonomy' => 'post_tag'],
        ], JSON_THROW_ON_ERROR)));
        $this->httpFakes()->respond('GET', '*wp/v2/tags*', $sequence);

        $tags = $this->wordPress->tags()->list();

        self::assertSame('PHP', $tags->all()[0]->name);
        self::assertSame('/wp-json/wp/v2/tags', $this->lastRequest()->getUri()->getPath());
    }

    public function testTermCreateAndDelete(): void
    {
        $create = new TestResponseSequence();
        $create->push(TestResponse::json(['id' => 6, 'name' => 'Tech'], 201));
        $this->httpFakes()->respond('POST', '*wp/v2/categories*', $create);

        $category = $this->wordPress->categories()->create(['name' => 'Tech']);

        self::assertSame(6, $category->id);

        $delete = new TestResponseSequence();
        $delete->push(TestResponse::json(['deleted' => true, 'previous' => ['id' => 6, 'name' => 'Tech']]));
        $this->httpFakes()->respond('DELETE', '*wp/v2/categories/6*', $delete);

        $deleted = $this->wordPress->categories()->delete(6, force: true);

        self::assertSame(6, $deleted->id);
    }
}
