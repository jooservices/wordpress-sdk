<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Services;

use JOOservices\Client\Testing\TestResponse;
use JOOservices\Client\Testing\TestResponseSequence;
use JOOservices\WordPress\Sdk\Data\SearchResult;
use JOOservices\WordPress\Sdk\Tests\TestCase;

final class SearchServiceTest extends TestCase
{
    public function testReturnsTypedResults(): void
    {
        $title = $this->faker->sentence(2);
        $search = $this->faker->word();
        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::make(200, ['X-WP-Total' => '1', 'X-WP-TotalPages' => '1'], json_encode([
            ['id' => 2, 'title' => $title, 'url' => $this->faker->url(), 'type' => 'post', 'subtype' => 'post'],
        ], JSON_THROW_ON_ERROR)));
        $this->httpFakes()->respond('GET', '*wp/v2/search*', $sequence);

        $results = $this->wordPress()->search()->search(['search' => $search]);

        self::assertInstanceOf(SearchResult::class, $results->all()[0]);
        self::assertSame($title, $results->all()[0]->title);
        $this->assertQuery($this->lastRequest(), ['search' => $search]);
    }
}
