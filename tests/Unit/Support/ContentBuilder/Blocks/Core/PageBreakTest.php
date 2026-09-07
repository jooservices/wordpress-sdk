<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Support\ContentBuilder\Blocks\Core;

use JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core\PageBreak;
use JOOservices\WordPress\Sdk\Tests\TestCase;

final class PageBreakTest extends TestCase
{
    public function testRenders(): void
    {
        self::assertSame("<!-- wp:nextpage -->\n<!--nextpage-->\n<!-- /wp:nextpage -->", (new PageBreak())->render());
    }
}
