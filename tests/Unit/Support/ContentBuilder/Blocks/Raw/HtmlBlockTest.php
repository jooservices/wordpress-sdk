<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Support\ContentBuilder\Blocks\Raw;

use JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Raw\HtmlBlock;
use JOOservices\WordPress\Sdk\Tests\TestCase;

final class HtmlBlockTest extends TestCase
{
    public function testRendersRawContent(): void
    {
        $content = $this->faker->word();
        self::assertSame(
            "<!-- wp:html -->\n<div>{$content}</div>\n<!-- /wp:html -->",
            (new HtmlBlock("<div>{$content}</div>"))->render(),
        );
    }
}
