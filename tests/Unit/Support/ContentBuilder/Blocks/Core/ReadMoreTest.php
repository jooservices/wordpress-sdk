<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Support\ContentBuilder\Blocks\Core;

use JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core\ReadMore;
use JOOservices\WordPress\Sdk\Tests\TestCase;

final class ReadMoreTest extends TestCase
{
    public function testSerializesCustomText(): void
    {
        $text = $this->faker->word();
        self::assertSame(
            "<!-- wp:more {\"customText\":\"{$text}\",\"noTeaser\":true} -->\n<!--more {$text}-->\n<!-- /wp:more -->",
            (new ReadMore($text, noTeaser: true))->render(),
        );
        self::assertSame("<!-- wp:more -->\n<!--more-->\n<!-- /wp:more -->", (new ReadMore())->render());
    }
}
