<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Data\Query;

use JOOservices\WordPress\Sdk\Data\Query\SearchQuery;
use JOOservices\WordPress\Sdk\Tests\TestCase;

final class SearchQueryTest extends TestCase
{
    public function testMapsSearchParameters(): void
    {
        self::assertSame(
            ['per_page' => 5, 'type' => 'post', 'subtype' => 'page'],
            (new SearchQuery(type: 'post', subtype: 'page', perPage: 5))->toQuery(),
        );
    }
}
