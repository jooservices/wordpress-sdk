<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Data\Query;

use JOOservices\WordPress\Sdk\Data\Query\ListPagesQuery;
use JOOservices\WordPress\Sdk\Tests\TestCase;

final class ListPagesQueryTest extends TestCase
{
    public function testMapsPageParameters(): void
    {
        self::assertSame(
            ['parent' => 5, 'parent_exclude' => [6], 'status' => 'draft'],
            (new ListPagesQuery(parent: 5, parentExclude: [6], status: 'draft'))->toQuery(),
        );
    }
}
