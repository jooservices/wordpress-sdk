<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Support\ContentBuilder\Blocks\Core;

use JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core\Button;
use JOOservices\WordPress\Sdk\Tests\TestCase;

final class ButtonTest extends TestCase
{
    public function testRendersValidMarkup(): void
    {
        $text = $this->faker->word();
        $url = $this->faker->url();
        self::assertSame(
            "<!-- wp:button -->\n<div class=\"wp-block-button\"><a class=\"wp-block-button__link wp-element-button\" href=\"{$url}\">{$text}</a></div>\n<!-- /wp:button -->",
            (new Button($text, $url))->render(),
        );
    }

    public function testEscapesUrlAttribute(): void
    {
        $rendered = (new Button($this->faker->word(), 'https://example.test/?q="x"'))->render();
        self::assertStringContainsString('href="https://example.test/?q=&quot;x&quot;"', $rendered);
    }

    public function testEscapesUntrustedText(): void
    {
        $payload = sprintf('<svg onload="%s">', $this->faker->word());
        $rendered = (new Button($payload, $this->faker->url()))->render();

        self::assertStringContainsString(
            htmlspecialchars($payload, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            $rendered,
        );
        self::assertStringNotContainsString($payload, $rendered);
    }
}
