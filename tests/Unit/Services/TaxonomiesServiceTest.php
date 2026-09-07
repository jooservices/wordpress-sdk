<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Services;

use JOOservices\Client\Testing\TestResponse;
use JOOservices\Client\Testing\TestResponseSequence;
use JOOservices\WordPress\Sdk\Data\Taxonomy;
use JOOservices\WordPress\Sdk\Tests\TestCase;

final class TaxonomiesServiceTest extends TestCase
{
    public function testGetsBySlug(): void
    {
        $name = $this->faker->word();
        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::json(['slug' => 'category', 'name' => $name]));
        $this->httpFakes()->respond('GET', '*wp/v2/taxonomies/category*', $sequence);

        $taxonomy = $this->wordPress()->taxonomies()->get('category');
        self::assertInstanceOf(Taxonomy::class, $taxonomy);
        self::assertSame($name, $taxonomy->name);
    }

    public function testEncodesSpecialCharactersInSlug(): void
    {
        $name = $this->faker->word();
        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::json(['slug' => 'a b', 'name' => $name]));
        $this->httpFakes()->respond('GET', '*wp/v2/taxonomies/a%20b*', $sequence);

        self::assertSame($name, $this->wordPress()->taxonomies()->get('a b')->name);
        self::assertSame('/wp-json/wp/v2/taxonomies/a%20b', $this->lastRequest()->getUri()->getPath());
    }

    public function testListsAssociativePayload(): void
    {
        $name = $this->faker->word();
        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::make(200, [], json_encode([
            'category' => ['slug' => 'category', 'name' => $name],
            'post_tag' => ['slug' => 'post_tag', 'name' => $this->faker->word()],
        ], JSON_THROW_ON_ERROR)));
        $this->httpFakes()->respond('GET', '*wp/v2/taxonomies*', $sequence);

        $taxonomies = $this->wordPress()->taxonomies()->list();
        self::assertCount(2, $taxonomies);
        self::assertSame($name, $taxonomies->all()[0]->name);
    }
}
