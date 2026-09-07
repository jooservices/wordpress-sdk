<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Support\ContentBuilder\Blocks\Core;

use JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core\Shortcode;
use JOOservices\WordPress\Sdk\Tests\TestCase;

final class ShortcodeTest extends TestCase
{
    public function testRendersRawContent(): void
    {
        self::assertSame(
            "<!-- wp:shortcode -->\n[gallery ids=\"1,2\"]\n<!-- /wp:shortcode -->",
            (new Shortcode('[gallery ids="1,2"]'))->render(),
        );
    }
}
