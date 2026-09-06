<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Data;

use JOOservices\WordPress\Sdk\Data\SearchResult;
use JOOservices\WordPress\Sdk\Tests\TestCase;

final class SearchResultTest extends TestCase
{
    public function testAcceptsStringIdentifier(): void
    {
        $result = SearchResult::from([
            'id' => 'plugin/acme',
            'title' => $this->faker->sentence(2),
            'url' => $this->faker->url(),
            'type' => 'post',
            'subtype' => 'plugin',
        ]);

        self::assertSame('plugin/acme', $result->id);
    }
}
