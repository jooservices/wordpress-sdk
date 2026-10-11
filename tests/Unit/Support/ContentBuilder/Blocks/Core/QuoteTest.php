<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Support\ContentBuilder\Blocks\Core;

use JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core\Quote;
use JOOservices\WordPress\Sdk\Tests\TestCase;
use ReflectionMethod;
use ReflectionParameter;

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

    public function testPreservesRawCitationByDefault(): void
    {
        $content = '<script>alert("quote")</script> & text';
        $citation = '<em>Author & Co.</em>';

        self::assertSame(
            '<!-- wp:quote -->' . "\n"
            . '<blockquote class="wp-block-quote"><p>' . $content . '</p>'
            . '<cite>' . $citation . '</cite></blockquote>' . "\n"
            . '<!-- /wp:quote -->',
            (new Quote($content, $citation))->render(),
        );
    }

    public function testEscapesRawContentWhenEnabled(): void
    {
        $content = '<script>alert("quote")</script> & text';
        $citation = '<em>Author & Co.</em>';

        self::assertSame(
            '<!-- wp:quote -->' . "\n"
            . '<blockquote class="wp-block-quote"><p>&lt;script&gt;alert(&quot;quote&quot;)&lt;/script&gt; &amp; text</p>'
            . '<cite>&lt;em&gt;Author &amp; Co.&lt;/em&gt;</cite></blockquote>' . "\n"
            . '<!-- /wp:quote -->',
            (new Quote($content, $citation, [], escapeHtml: true))->render(),
        );
    }

    public function testAllowsExplicitRawContentAndCitation(): void
    {
        $content = '<strong>Trusted</strong> & <i>markup</i>';
        $citation = '<a href="https://example.test">Source</a>';

        self::assertSame(
            '<!-- wp:quote -->' . "\n"
            . '<blockquote class="wp-block-quote"><p>' . $content . '</p><cite>' . $citation . '</cite></blockquote>' . "\n"
            . '<!-- /wp:quote -->',
            (new Quote($content, $citation, [], escapeHtml: false))->render(),
        );
    }

    public function testPreservesCitationAttribute(): void
    {
        $content = $this->faker->sentence();
        $citation = $this->faker->uuid();

        self::assertSame(
            sprintf(
                "<!-- wp:quote {\"citation\":\"%s\"} -->\n<blockquote class=\"wp-block-quote\"><p>%s</p></blockquote>\n<!-- /wp:quote -->",
                $citation,
                $content,
            ),
            (new Quote($content, attributes: ['citation' => $citation]))->render(),
        );
    }

    public function testKeepsAttributesWhenRenderingCitation(): void
    {
        $content = $this->faker->sentence();
        $citation = $this->faker->uuid();
        $attributeCitation = $this->faker->uuid();

        $rendered = (new Quote(
            $content,
            $citation,
            ['citation' => $attributeCitation],
        ))->render();

        self::assertStringContainsString(sprintf('{"citation":"%s"}', $attributeCitation), $rendered);
        self::assertStringContainsString(sprintf('<cite>%s</cite>', $citation), $rendered);
    }

    public function testConstructorCannotSetParserCitation(): void
    {
        $constructor = new ReflectionMethod(Quote::class, '__construct');
        $parameterNames = array_map(
            static fn(ReflectionParameter $parameter): string => $parameter->getName(),
            $constructor->getParameters(),
        );

        self::assertSame(['content', 'citation', 'attributes', 'escapeHtml'], $parameterNames);
    }

    public function testParsedMarkupFactoryEscapesByDefault(): void
    {
        $content = $this->faker->sentence();
        $hostileCitation = '<img src=x onerror=alert(1)>';

        self::assertStringContainsString(
            '<cite>&lt;img src=x onerror=alert(1)&gt;</cite>',
            Quote::fromParsedMarkup($content, '', [], $hostileCitation)->toHtml(),
        );
    }
}
