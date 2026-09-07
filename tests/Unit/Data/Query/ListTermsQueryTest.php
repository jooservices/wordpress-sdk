<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Data\Query;

use JOOservices\WordPress\Sdk\Data\Query\ListTermsQuery;
use JOOservices\WordPress\Sdk\Tests\TestCase;

final class ListTermsQueryTest extends TestCase
{
    public function testMapsTermParameters(): void
    {
        $slug = $this->faker->slug();
        self::assertSame(
            ['hide_empty' => true, 'parent' => 2, 'post' => 9, 'slug' => [$slug]],
            (new ListTermsQuery(hideEmpty: true, parent: 2, post: 9, slug: [$slug]))->toQuery(),
        );
    }
}
