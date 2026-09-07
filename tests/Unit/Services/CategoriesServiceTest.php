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
        $name = $this->faker->word();
        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::json(['id' => 5, 'name' => $name, 'taxonomy' => 'category']));
        $this->httpFakes()->respond('GET', '*wp/v2/categories/5*', $sequence);

        $category = $this->wordPress->categories()->get(5);

        self::assertInstanceOf(Term::class, $category);
        self::assertSame($name, $category->name);
        self::assertSame('/wp-json/wp/v2/categories/5', $this->lastRequest()->getUri()->getPath());
    }

    public function testTermCreateAndDelete(): void
    {
        $name = $this->faker->word();
        $create = new TestResponseSequence();
        $create->push(TestResponse::json(['id' => 6, 'name' => $name], 201));
        $this->httpFakes()->respond('POST', '*wp/v2/categories*', $create);

        $category = $this->wordPress->categories()->create(['name' => $name]);

        self::assertSame(6, $category->id);

        $delete = new TestResponseSequence();
        $delete->push(TestResponse::json(['deleted' => true, 'previous' => ['id' => 6, 'name' => $name]]));
        $this->httpFakes()->respond('DELETE', '*wp/v2/categories/6*', $delete);

        $deleted = $this->wordPress->categories()->delete(6, force: true);

        self::assertSame(6, $deleted->id);
    }
}
