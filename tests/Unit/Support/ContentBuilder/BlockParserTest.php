<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Support\ContentBuilder;

use InvalidArgumentException;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\BlockRegistry;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core\Button;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core\Column;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core\Columns;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core\Group;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core\Heading;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core\Image;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core\Paragraph;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core\Quote;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core\ReadMore;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core\ReadMoreButton;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core\Separator;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Raw\HtmlBlock;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\GenericBlock;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\Parser\BlockParser;
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
        $blocks = $this->parser->parse("Just text\n\nMore text", $this->registry);

        self::assertCount(1, $blocks);
        self::assertInstanceOf(HtmlBlock::class, $blocks[0]);
        self::assertSame("Just text\n\nMore text", $blocks[0]->toHtml());
    }

    public function testParsesParagraph(): void
    {
        $blocks = $this->parser->parse(
            "<!-- wp:paragraph -->\n<p>Hello</p>\n<!-- /wp:paragraph -->",
            $this->registry,
        );

        self::assertCount(1, $blocks);
        self::assertInstanceOf(Paragraph::class, $blocks[0]);
        self::assertSame('Hello', $blocks[0]->text);
        self::assertSame([], $blocks[0]->attributes);
    }

    public function testParsesHeadingWithLevel(): void
    {
        $blocks = $this->parser->parse(
            "<!-- wp:heading {\"level\":3,\"anchor\":\"x\"} -->\n<h3>Title</h3>\n<!-- /wp:heading -->",
            $this->registry,
        );

        self::assertInstanceOf(Heading::class, $blocks[0]);
        self::assertSame('Title', $blocks[0]->text);
        self::assertSame(3, $blocks[0]->level);
        self::assertSame(['level' => 3, 'anchor' => 'x'], $blocks[0]->attributes);
    }

    public function testParsesImageFromAttributesAndMarkup(): void
    {
        $blocks = $this->parser->parse(
            "<!-- wp:image {\"id\":4,\"sizeSlug\":\"full\",\"linkDestination\":\"none\"} -->\n"
            . '<figure class="wp-block-image size-full"><img src="https://example.test/a.png" alt="Alt" class="wp-image-4"/></figure>'
            . "\n<!-- /wp:image -->",
            $this->registry,
        );

        self::assertInstanceOf(Image::class, $blocks[0]);
        self::assertSame(4, $blocks[0]->mediaId);
        self::assertSame('https://example.test/a.png', $blocks[0]->src);
        self::assertSame('Alt', $blocks[0]->alt);
    }

    public function testParsesQuoteWithCitation(): void
    {
        $blocks = $this->parser->parse(
            "<!-- wp:quote {\"citation\":\"Author\"} -->\n<blockquote class=\"wp-block-quote\"><p>Text</p><cite>Author</cite></blockquote>\n<!-- /wp:quote -->",
            $this->registry,
        );

        self::assertInstanceOf(Quote::class, $blocks[0]);
        self::assertSame('Text', $blocks[0]->content);
        self::assertSame('Author', $blocks[0]->citation);
    }

    public function testParsesReadMoreWithCustomText(): void
    {
        $blocks = $this->parser->parse(
            "<!-- wp:more {\"customText\":\"Read on\"} -->\n<!--more Read on-->\n<!-- /wp:more -->",
            $this->registry,
        );

        self::assertInstanceOf(ReadMore::class, $blocks[0]);
        self::assertSame('Read on', $blocks[0]->customText);
    }

    public function testParsesButton(): void
    {
        $blocks = $this->parser->parse(
            "<!-- wp:button -->\n"
            . '<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="https://example.test">Go</a></div>'
            . "\n<!-- /wp:button -->",
            $this->registry,
        );

        self::assertInstanceOf(Button::class, $blocks[0]);
        self::assertSame('Go', $blocks[0]->text);
        self::assertSame('https://example.test', $blocks[0]->url);
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

    public function testParsesContainersWithChildren(): void
    {
        $markup = "<!-- wp:columns -->\n"
            . '<div class="wp-block-columns">'
            . "<!-- wp:column -->\n<div class=\"wp-block-column\">"
            . "<!-- wp:paragraph -->\n<p>Cell</p>\n<!-- /wp:paragraph -->"
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
        $markup = "<!-- wp:group {\"tagName\":\"section\"} -->\n"
            . '<section class="wp-block-group">'
            . "<!-- wp:paragraph -->\n<p>In group</p>\n<!-- /wp:paragraph -->"
            . "</section>\n<!-- /wp:group -->";

        $blocks = $this->parser->parse($markup, $this->registry);

        self::assertInstanceOf(Group::class, $blocks[0]);
        self::assertSame('section', $blocks[0]->tagName);
        self::assertCount(1, $blocks[0]->getInnerBlocks());
    }

    public function testKeepsNonWrapperTextInsideContainers(): void
    {
        $markup = "<!-- wp:group -->\n"
            . '<div class="wp-block-group">real text'
            . "<!-- wp:paragraph -->\n<p>P</p>\n<!-- /wp:paragraph -->"
            . "</div>\n<!-- /wp:group -->";

        $blocks = $this->parser->parse($markup, $this->registry);

        self::assertInstanceOf(Group::class, $blocks[0]);
        self::assertCount(2, $blocks[0]->getInnerBlocks());
        self::assertInstanceOf(HtmlBlock::class, $blocks[0]->getInnerBlocks()[0]);
    }

    public function testUnknownBlocksBecomeGenericBlockWithoutCorePrefix(): void
    {
        $blocks = $this->parser->parse(
            "<!-- wp:my-plugin/widget {\"a\":1} -->\ninner\n<!-- /wp:my-plugin/widget -->",
            $this->registry,
        );

        self::assertInstanceOf(GenericBlock::class, $blocks[0]);
        self::assertSame('my-plugin/widget', $blocks[0]->name);
        self::assertSame(['a' => 1], $blocks[0]->attributes);
    }

    public function testMalformedMarkupDegradesToHtmlBlock(): void
    {
        $blocks = $this->parser->parse('<!-- wp:paragraph', $this->registry);

        self::assertCount(1, $blocks);
        self::assertInstanceOf(HtmlBlock::class, $blocks[0]);

        $blocks = $this->parser->parse(
            "<!-- wp:paragraph -->\n<p>No closer</p>",
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
        $source = "<!-- wp:group {\"tagName\":\"div\"} -->\n"
            . '<div class="wp-block-group">'
            . "<!-- wp:group {\"tagName\":\"section\"} -->\n"
            . '<section class="wp-block-group">'
            . "<!-- wp:paragraph -->\n<p>Nested</p>\n<!-- /wp:paragraph -->"
            . "</section>\n<!-- /wp:group -->"
            . "</div>\n<!-- /wp:group -->";

        $blocks = $this->parser->parse($source, $this->registry);

        self::assertCount(1, $blocks);
        self::assertInstanceOf(Group::class, $blocks[0]);
        self::assertSame($source, $blocks[0]->render());
    }

    public function testRoundTripOfComplexDocument(): void
    {
        $source = "<!-- wp:heading {\"level\":2} -->\n<h2>Intro</h2>\n<!-- /wp:heading -->\n\n"
            . "<!-- wp:paragraph -->\n<p>Body text</p>\n<!-- /wp:paragraph -->\n\n"
            . "<!-- wp:quote -->\n<blockquote class=\"wp-block-quote\"><p>Cited</p></blockquote>\n<!-- /wp:quote -->";

        $blocks = $this->parser->parse($source, $this->registry);

        self::assertCount(3, $blocks);
        self::assertSame(
            implode("\n\n", array_map(static fn($block): string => $block->render(), $blocks)),
            $source,
        );
    }
}
