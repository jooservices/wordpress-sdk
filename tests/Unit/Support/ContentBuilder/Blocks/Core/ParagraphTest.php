<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Support\ContentBuilder\Blocks\Core;

use JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core\Paragraph;
use JOOservices\WordPress\Sdk\Tests\TestCase;

final class ParagraphTest extends TestCase
{
    public function testRendersWithAlignClass(): void
    {
        $content = $this->faker->sentence();
        $block = new Paragraph($content, ['align' => 'center']);

        self::assertSame(
            "<!-- wp:paragraph {\"align\":\"center\"} -->\n<p class=\"has-text-align-center\">{$content}</p>\n<!-- /wp:paragraph -->",
            $block->render(),
        );
        self::assertSame("<p class=\"has-text-align-center\">{$content}</p>", $block->toHtml());
    }

    public function testEscapesUntrustedText(): void
    {
        $payload = sprintf('<script>alert("%s")</script>', $this->faker->word());

        self::assertStringContainsString(
            htmlspecialchars($payload, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            (new Paragraph($payload))->render(),
        );
    }
}
