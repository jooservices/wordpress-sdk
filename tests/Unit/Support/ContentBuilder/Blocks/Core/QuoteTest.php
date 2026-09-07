<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Support\ContentBuilder\Blocks\Core;

use JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core\Quote;
use JOOservices\WordPress\Sdk\Tests\TestCase;

final class QuoteTest extends TestCase
{
    public function testRendersWithoutCitation(): void
    {
        $content = $this->faker->sentence();
        self::assertSame(
            "<!-- wp:quote -->\n<blockquote class=\"wp-block-quote\"><p>{$content}</p></blockquote>\n<!-- /wp:quote -->",
            (new Quote($content))->render(),
        );
    }

    public function testRendersMultipleParagraphs(): void
    {
        $first = $this->faker->sentence();
        $second = $this->faker->sentence();
        self::assertSame(
            "<!-- wp:quote -->\n<blockquote class=\"wp-block-quote\"><p>{$first}</p><p>{$second}</p></blockquote>\n<!-- /wp:quote -->",
            (new Quote("{$first}\n\n{$second}"))->render(),
        );
    }
}
