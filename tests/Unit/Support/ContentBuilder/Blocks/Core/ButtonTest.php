<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Support\ContentBuilder\Blocks\Core;

use InvalidArgumentException;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core\Button;
use JOOservices\WordPress\Sdk\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

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
        $domain = $this->faker->domainName();
        $query = $this->faker->word();
        $url = sprintf('https://%s/?q="%s"', $domain, $query);
        $rendered = (new Button($this->faker->word(), $url))->render();

        self::assertStringContainsString(
            sprintf('href="https://%s/?q=&quot;%s&quot;"', $domain, $query),
            $rendered,
        );
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

    #[DataProvider('executableUrls')]
    public function testRejectsObfuscatedExecutableHrefs(string $url): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new Button($this->faker->word(), $url))->render();
    }

    /**
     * @return array<string, array{string}>
     */
    public static function executableUrls(): array
    {
        return [
            'javascript scheme' => ['javascript:alert(1)'],
            'case insensitive scheme' => ['JaVaScRiPt:alert(1)'],
            'javascript with ASCII whitespace and controls' => ["java\x00\x09\x0A\x0D\x20\x7Fscript:alert(1)"],
            'data scheme' => ['data:text/html,unsafe'],
            'obfuscated data scheme' => ["d\tata:text/html,unsafe"],
            'vbscript scheme' => ['vbscript:msgbox(1)'],
            'obfuscated vbscript scheme' => ["vb\x0Bscript:msgbox(1)"],
        ];
    }

    public function testPreservesSafeCustomSchemes(): void
    {
        $httpsUrl = $this->faker->url();
        $customUrl = 'web+test:' . $this->faker->slug();
        $text = $this->faker->word();

        self::assertStringContainsString(
            sprintf('href="%s"', htmlspecialchars($httpsUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')),
            (new Button($text, $httpsUrl))->toHtml(),
        );
        self::assertStringContainsString(
            sprintf('href="%s"', htmlspecialchars($customUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')),
            (new Button($text, $customUrl))->toHtml(),
        );
    }
}
