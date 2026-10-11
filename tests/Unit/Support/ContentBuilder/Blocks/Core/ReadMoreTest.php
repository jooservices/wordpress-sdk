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

    public function testEscapesCommentTerminatorInCustomText(): void
    {
        $payload = 'Read more --> <script>alert(1)</script>';
        $rendered = (new ReadMore($payload))->render();

        self::assertSame(
            "<!-- wp:more {\"customText\":\"Read more --\\u003E \\u003Cscript\\u003Ealert(1)\\u003C/script\\u003E\"} -->\n<!--more Read more --&gt; &lt;script&gt;alert(1)&lt;/script&gt;-->\n<!-- /wp:more -->",
            $rendered,
        );
        self::assertStringNotContainsString('--> <script>', $rendered);
    }
}
