<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Support\ContentBuilder\Parser;

use InvalidArgumentException;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\BlockRegistry;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core\Button;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core\Column;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core\Columns;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core\Code;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core\Group;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core\Heading;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core\Image;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core\Paragraph;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core\PageBreak;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core\Quote;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core\ReadMore;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core\ReadMoreButton;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core\Separator;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core\Shortcode;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Raw\HtmlBlock;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\GenericBlock;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\Parser\BlockParser;
use JOOservices\WordPress\Sdk\Tests\Fixtures\CustomBlock;
use JOOservices\WordPress\Sdk\Tests\TestCase;

final class BlockParserTest extends TestCase
{
    private BlockParser $parser;

    private BlockRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new BlockParser();
        $this->registry = new BlockRegistry();
    }

    public function testParsesPlainTextAsHtmlBlock(): void
    {
        $content = $this->faker->paragraph() . "\n\n" . $this->faker->paragraph();
        $blocks = $this->parser->parse($content, $this->registry);

        self::assertCount(1, $blocks);
        self::assertInstanceOf(HtmlBlock::class, $blocks[0]);
        self::assertSame($content, $blocks[0]->toHtml());
    }

    public function testParsesParagraph(): void
    {
        $content = $this->faker->sentence();
        $blocks = $this->parser->parse(
            "<!-- wp:paragraph -->\n<p>{$content}</p>\n<!-- /wp:paragraph -->",
            $this->registry,
        );

        self::assertCount(1, $blocks);
        self::assertInstanceOf(Paragraph::class, $blocks[0]);
        self::assertSame($content, $blocks[0]->text);
        self::assertSame([], $blocks[0]->attributes);
    }

    public function testParsesParagraphPreservesInlineMarkup(): void
    {
        $first = $this->faker->word();
        $strong = $this->faker->word();
        $emphasis = $this->faker->word();
        $content = "{$first} <strong>{$strong}</strong> and <em>{$emphasis}</em>";
        $blocks = $this->parser->parse(
            "<!-- wp:paragraph -->\n<p>{$content}</p>\n<!-- /wp:paragraph -->",
            $this->registry,
        );

        self::assertInstanceOf(Paragraph::class, $blocks[0]);
        self::assertSame($content, $blocks[0]->text);
        self::assertSame(
            "<!-- wp:paragraph -->\n<p>{$content}</p>\n<!-- /wp:paragraph -->",
            $blocks[0]->render(),
        );
    }

    public function testRegisteredOverrideTakesPrecedenceOverCoreParser(): void
    {
        $content = $this->faker->sentence();
        $attributes = ['anchor' => $this->faker->slug()];
        $this->registry->register('core/paragraph', CustomBlock::class);

        $blocks = $this->parser->parse(
            sprintf(
                "<!-- wp:paragraph %s -->\n<p>%s</p>\n<!-- /wp:paragraph -->",
                json_encode($attributes, JSON_THROW_ON_ERROR),
                $content,
            ),
            $this->registry,
        );

        self::assertInstanceOf(CustomBlock::class, $blocks[0]);
        self::assertSame("\n<p>{$content}</p>\n", $blocks[0]->content);
        self::assertSame($attributes, $blocks[0]->attributes);
    }

    public function testParsesHeadingWithLevel(): void
    {
        $title = $this->faker->sentence();
        $blocks = $this->parser->parse(
            "<!-- wp:heading {\"level\":3,\"anchor\":\"x\"} -->\n<h3>{$title}</h3>\n<!-- /wp:heading -->",
            $this->registry,
        );

        self::assertInstanceOf(Heading::class, $blocks[0]);
        self::assertSame($title, $blocks[0]->text);
        self::assertSame(3, $blocks[0]->level);
        self::assertSame(['level' => 3, 'anchor' => 'x'], $blocks[0]->attributes);
    }

    public function testParsesImageFromAttributesAndMarkup(): void
    {
        $sourceUrl = $this->faker->imageUrl();
        $alt = $this->faker->word();
        $blocks = $this->parser->parse(
            "<!-- wp:image {\"id\":4,\"sizeSlug\":\"full\",\"linkDestination\":\"none\"} -->\n"
            . "<figure class=\"wp-block-image size-full\"><img src=\"{$sourceUrl}\" alt=\"{$alt}\" class=\"wp-image-4\"/></figure>"
            . "\n<!-- /wp:image -->",
            $this->registry,
        );

        self::assertInstanceOf(Image::class, $blocks[0]);
        self::assertSame(4, $blocks[0]->mediaId);
        self::assertSame($sourceUrl, $blocks[0]->src);
        self::assertSame($alt, $blocks[0]->alt);
    }

    public function testParsesQuoteWithCitation(): void
    {
        $content = $this->faker->sentence();
        $citation = $this->faker->word();
        $blocks = $this->parser->parse(
            "<!-- wp:quote {\"citation\":\"{$citation}\"} -->\n<blockquote class=\"wp-block-quote\"><p>{$content}</p><cite>{$citation}</cite></blockquote>\n<!-- /wp:quote -->",
            $this->registry,
        );

        self::assertInstanceOf(Quote::class, $blocks[0]);
        self::assertSame($content, $blocks[0]->content);
        self::assertSame($citation, $blocks[0]->citation);
    }

    public function testParsesQuoteWithMultipleParagraphs(): void
    {
        $first = $this->faker->sentence();
        $second = $this->faker->sentence();
        $source = "<!-- wp:quote -->\n"
            . "<blockquote class=\"wp-block-quote\"><p>{$first}</p><p>{$second}</p></blockquote>"
            . "\n<!-- /wp:quote -->";

        $blocks = $this->parser->parse($source, $this->registry);

        self::assertInstanceOf(Quote::class, $blocks[0]);
        self::assertSame("{$first}\n\n{$second}", $blocks[0]->content);
        self::assertSame($source, $blocks[0]->render());
    }

    public function testParsesReadMoreWithCustomText(): void
    {
        $customText = $this->faker->word();
        $blocks = $this->parser->parse(
            "<!-- wp:more {\"customText\":\"{$customText}\"} -->\n<!--more {$customText}-->\n<!-- /wp:more -->",
            $this->registry,
        );

        self::assertInstanceOf(ReadMore::class, $blocks[0]);
        self::assertSame($customText, $blocks[0]->customText);
    }

    public function testParsesButton(): void
    {
        $text = $this->faker->word();
        $url = $this->faker->url();
        $blocks = $this->parser->parse(
            "<!-- wp:button -->\n"
            . "<div class=\"wp-block-button\"><a class=\"wp-block-button__link wp-element-button\" href=\"{$url}\">{$text}</a></div>"
            . "\n<!-- /wp:button -->",
            $this->registry,
        );

        self::assertInstanceOf(Button::class, $blocks[0]);
        self::assertSame($text, $blocks[0]->text);
        self::assertSame($url, $blocks[0]->url);
    }

    public function testParsesSeparatorAndReadMoreButton(): void
    {
        $blocks = $this->parser->parse(
            "<!-- wp:separator -->\n<hr class=\"wp-block-separator has-alpha-channel-opacity\"/>\n<!-- /wp:separator -->\n\n"
            . "<!-- wp:read-more /-->",
            $this->registry,
        );

        self::assertInstanceOf(Separator::class, $blocks[0]);
        self::assertInstanceOf(ReadMoreButton::class, $blocks[1]);
    }

    public function testParsesCodeShortcodeHtmlAndPageBreakLeaves(): void
    {
        $code = $this->faker->word();
        $shortcode = sprintf('[%s]', $this->faker->slug());
        $html = sprintf('<div>%s</div>', $this->faker->sentence());
        $source = "<!-- wp:code -->\n<pre><code>{$code}</code></pre>\n<!-- /wp:code -->\n"
            . "<!-- wp:shortcode -->\n{$shortcode}\n<!-- /wp:shortcode -->\n"
            . "<!-- wp:html -->\n{$html}\n<!-- /wp:html -->\n"
            . '<!-- wp:nextpage /-->';

        $blocks = $this->parser->parse($source, $this->registry);

        self::assertInstanceOf(Code::class, $blocks[0]);
        self::assertInstanceOf(Shortcode::class, $blocks[1]);
        self::assertInstanceOf(HtmlBlock::class, $blocks[2]);
        self::assertInstanceOf(PageBreak::class, $blocks[3]);
    }

    public function testParsesContainersWithChildren(): void
    {
        $content = $this->faker->word();
        $markup = "<!-- wp:columns -->\n"
            . '<div class="wp-block-columns">'
            . "<!-- wp:column -->\n<div class=\"wp-block-column\">"
            . "<!-- wp:paragraph -->\n<p>{$content}</p>\n<!-- /wp:paragraph -->"
            . "</div>\n<!-- /wp:column -->"
            . "</div>\n<!-- /wp:columns -->";

        $blocks = $this->parser->parse($markup, $this->registry);

        self::assertCount(1, $blocks);
        self::assertInstanceOf(Columns::class, $blocks[0]);

        $column = $blocks[0]->getInnerBlocks()[0];
        self::assertInstanceOf(Column::class, $column);
        self::assertInstanceOf(Paragraph::class, $column->getInnerBlocks()[0]);
        self::assertCount(1, $column->getInnerBlocks());
    }

    public function testParsesGroup(): void
    {
        $content = $this->faker->sentence();
        $markup = "<!-- wp:group {\"tagName\":\"section\"} -->\n"
            . '<section class="wp-block-group">'
            . "<!-- wp:paragraph -->\n<p>{$content}</p>\n<!-- /wp:paragraph -->"
            . "</section>\n<!-- /wp:group -->";

        $blocks = $this->parser->parse($markup, $this->registry);

        self::assertInstanceOf(Group::class, $blocks[0]);
        self::assertSame('section', $blocks[0]->tagName);
        self::assertCount(1, $blocks[0]->getInnerBlocks());
    }

    public function testKeepsNonWrapperTextInsideContainers(): void
    {
        $raw = $this->faker->sentence(2);
        $paragraph = $this->faker->word();
        $markup = "<!-- wp:group -->\n"
            . "<div class=\"wp-block-group\">{$raw}"
            . "<!-- wp:paragraph -->\n<p>{$paragraph}</p>\n<!-- /wp:paragraph -->"
            . "</div>\n<!-- /wp:group -->";

        $blocks = $this->parser->parse($markup, $this->registry);

        self::assertInstanceOf(Group::class, $blocks[0]);
        self::assertCount(2, $blocks[0]->getInnerBlocks());
        self::assertInstanceOf(HtmlBlock::class, $blocks[0]->getInnerBlocks()[0]);
    }

    public function testUnknownBlocksBecomeGenericBlockWithoutCorePrefix(): void
    {
        $inner = $this->faker->word();
        $blocks = $this->parser->parse(
            "<!-- wp:my-plugin/widget {\"a\":1} -->\n{$inner}\n<!-- /wp:my-plugin/widget -->",
            $this->registry,
        );

        self::assertInstanceOf(GenericBlock::class, $blocks[0]);
        self::assertSame('my-plugin/widget', $blocks[0]->name);
        self::assertSame(['a' => 1], $blocks[0]->attributes);
    }

    public function testMalformedMarkupDegradesToHtmlBlock(): void
    {
        $content = $this->faker->sentence();
        $blocks = $this->parser->parse('<!-- wp:paragraph', $this->registry);

        self::assertCount(1, $blocks);
        self::assertInstanceOf(HtmlBlock::class, $blocks[0]);

        $blocks = $this->parser->parse(
            "<!-- wp:paragraph -->\n<p>{$content}</p>",
            $this->registry,
        );

        self::assertCount(1, $blocks);
        self::assertInstanceOf(HtmlBlock::class, $blocks[0]);
    }

    public function testInvalidAttributesJsonIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->parser->parse(
            "<!-- wp:paragraph not-json -->\n<p>x</p>\n<!-- /wp:paragraph -->",
            $this->registry,
        );
    }

    public function testRoundTripsNestedBlocksWithTheSameName(): void
    {
        $content = $this->faker->word();
        $source = "<!-- wp:group {\"tagName\":\"div\"} -->\n"
            . '<div class="wp-block-group">'
            . "<!-- wp:group {\"tagName\":\"section\"} -->\n"
            . '<section class="wp-block-group">'
            . "<!-- wp:paragraph -->\n<p>{$content}</p>\n<!-- /wp:paragraph -->"
            . "</section>\n<!-- /wp:group -->"
            . "</div>\n<!-- /wp:group -->";

        $blocks = $this->parser->parse($source, $this->registry);

        self::assertCount(1, $blocks);
        self::assertInstanceOf(Group::class, $blocks[0]);
        self::assertSame($source, $blocks[0]->render());
    }

    public function testRoundTripOfComplexDocument(): void
    {
        $heading = $this->faker->sentence();
        $paragraph = $this->faker->paragraph();
        $quote = $this->faker->sentence();
        $source = "<!-- wp:heading {\"level\":2} -->\n<h2>{$heading}</h2>\n<!-- /wp:heading -->\n\n"
            . "<!-- wp:paragraph -->\n<p>{$paragraph}</p>\n<!-- /wp:paragraph -->\n\n"
            . "<!-- wp:quote -->\n<blockquote class=\"wp-block-quote\"><p>{$quote}</p></blockquote>\n<!-- /wp:quote -->";

        $blocks = $this->parser->parse($source, $this->registry);

        self::assertCount(3, $blocks);
        self::assertSame(
            implode("\n\n", array_map(static fn($block): string => $block->render(), $blocks)),
            $source,
        );
    }
}
