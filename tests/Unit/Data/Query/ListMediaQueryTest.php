<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Data\Query;

use JOOservices\WordPress\Sdk\Data\Query\ListMediaQuery;
use JOOservices\WordPress\Sdk\Tests\TestCase;

final class ListMediaQueryTest extends TestCase
{
    public function testMapsMediaParameters(): void
    {
        self::assertSame(
            ['parent' => 3, 'media_type' => 'image', 'mime_type' => 'image/png'],
            (new ListMediaQuery(parent: 3, mediaType: 'image', mimeType: 'image/png'))->toQuery(),
        );
    }
}
