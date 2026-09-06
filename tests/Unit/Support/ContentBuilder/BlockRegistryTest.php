<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Support\ContentBuilder;

use JOOservices\WordPress\Sdk\Support\ContentBuilder\BlockRegistry;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core\Button;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core\Buttons;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core\Code;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core\Column;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core\Columns;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core\Group;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core\Heading;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core\Image;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core\PageBreak;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core\Paragraph;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core\Quote;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core\ReadMore;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core\ReadMoreButton;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core\Separator;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core\Shortcode;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Raw\HtmlBlock;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\GenericBlock;
use JOOservices\WordPress\Sdk\Tests\TestCase;
use InvalidArgumentException;

final class BlockRegistryTest extends TestCase
{
    public function testDefaultRegistryRegistersCoreBlocks(): void
    {
        $registry = new BlockRegistry();

        self::assertSame(Paragraph::class, $registry->get('core/paragraph'));
        self::assertSame(Heading::class, $registry->get('core/heading'));
        self::assertSame(Image::class, $registry->get('core/image'));
        self::assertSame(Quote::class, $registry->get('core/quote'));
        self::assertSame(ReadMore::class, $registry->get('core/more'));
        self::assertSame(ReadMoreButton::class, $registry->get('core/read-more'));
        self::assertSame(PageBreak::class, $registry->get('core/nextpage'));
        self::assertSame(Separator::class, $registry->get('core/separator'));
        self::assertSame(Code::class, $registry->get('core/code'));
        self::assertSame(Shortcode::class, $registry->get('core/shortcode'));
        self::assertSame(HtmlBlock::class, $registry->get('core/html'));
        self::assertSame(Button::class, $registry->get('core/button'));
        self::assertSame(Buttons::class, $registry->get('core/buttons'));
        self::assertSame(Column::class, $registry->get('core/column'));
        self::assertSame(Columns::class, $registry->get('core/columns'));
        self::assertSame(Group::class, $registry->get('core/group'));
    }

    public function testRegistryRegisterUnregisterHasAll(): void
    {
        $registry = new BlockRegistry();
        $registry->register('my-plugin/block', GenericBlock::class);

        self::assertTrue($registry->has('my-plugin/block'));
        self::assertCount(17, $registry->all());

        $registry->unregister('my-plugin/block');

        self::assertFalse($registry->has('my-plugin/block'));
    }

    public function testRegistryRejectsNonBlockClasses(): void
    {
        $registry = new BlockRegistry();

        $this->expectException(InvalidArgumentException::class);

        $registry->register('core/x', 'NonExistent\\BlockClass');
    }

    public function testRegistryGetUnknownBlockThrows(): void
    {
        $registry = new BlockRegistry();

        $this->expectException(InvalidArgumentException::class);

        $registry->get('core/unknown');
    }

    public function testParagraphRendersWithAlignClass(): void
    {
        $block = new Paragraph('Hello', ['align' => 'center']);

        self::assertSame(
            "<!-- wp:paragraph {\"align\":\"center\"} -->\n<p class=\"has-text-align-center\">Hello</p>\n<!-- /wp:paragraph -->",
            $block->render(),
        );
        self::assertSame('<p class="has-text-align-center">Hello</p>', $block->toHtml());
    }

    public function testHeadingLevelIsOnlySerializedWhenNotDefault(): void
    {
        self::assertSame(
            "<!-- wp:heading -->\n<h2>Title</h2>\n<!-- /wp:heading -->",
            (new Heading('Title'))->render(),
        );
        self::assertSame(
            "<!-- wp:heading {\"level\":3} -->\n<h3>Title</h3>\n<!-- /wp:heading -->",
            (new Heading('Title', 3))->render(),
        );
    }

    public function testHeadingRejectsInvalidLevels(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Heading('Title', 7);
    }

    public function testImageRendersWithDefaults(): void
    {
        $block = new Image(4, 'https://example.test/a.png', 'Alt');

        self::assertSame(
            "<!-- wp:image {\"id\":4,\"sizeSlug\":\"full\",\"linkDestination\":\"none\"} -->\n"
            . '<figure class="wp-block-image size-full"><img src="https://example.test/a.png" alt="Alt" class="wp-image-4"/></figure>'
            . "\n<!-- /wp:image -->",
            $block->render(),
        );
    }

    public function testImageEscapesAttributeValues(): void
    {
        $rendered = (new Image(4, 'https://example.test/a.png?x="y"', 'A "quote"'))->render();

        self::assertStringContainsString('src="https://example.test/a.png?x=&quot;y&quot;"', $rendered);
        self::assertStringContainsString('alt="A &quot;quote&quot;"', $rendered);
    }

    public function testQuoteRendersWithoutCitation(): void
    {
        self::assertSame(
            "<!-- wp:quote -->\n<blockquote class=\"wp-block-quote\"><p>Text</p></blockquote>\n<!-- /wp:quote -->",
            (new Quote('Text'))->render(),
        );
    }

    public function testQuoteRendersMultipleParagraphs(): void
    {
        self::assertSame(
            "<!-- wp:quote -->\n<blockquote class=\"wp-block-quote\"><p>First</p><p>Second</p></blockquote>\n<!-- /wp:quote -->",
            (new Quote("First\n\nSecond"))->render(),
        );
    }

    public function testReadMoreSerializesCustomText(): void
    {
        self::assertSame(
            "<!-- wp:more {\"customText\":\"Read on\",\"noTeaser\":true} -->\n<!--more Read on-->\n<!-- /wp:more -->",
            (new ReadMore('Read on', noTeaser: true))->render(),
        );
        self::assertSame(
            "<!-- wp:more -->\n<!--more-->\n<!-- /wp:more -->",
            (new ReadMore())->render(),
        );
    }

    public function testCodeEscapesContent(): void
    {
        self::assertSame(
            "<!-- wp:code -->\n<pre class=\"wp-block-code\"><code>&lt;?php echo &#039;x&#039;; ?&gt;</code></pre>\n<!-- /wp:code -->",
            (new Code("<?php echo 'x'; ?>"))->render(),
        );
    }

    public function testShortcodeRendersRaw(): void
    {
        self::assertSame(
            "<!-- wp:shortcode -->\n[gallery ids=\"1,2\"]\n<!-- /wp:shortcode -->",
            (new Shortcode('[gallery ids="1,2"]'))->render(),
        );
    }

    public function testHtmlBlockRendersRaw(): void
    {
        self::assertSame(
            "<!-- wp:html -->\n<div>raw</div>\n<!-- /wp:html -->",
            (new HtmlBlock('<div>raw</div>'))->render(),
        );
    }

    public function testButtonRendersValidMarkup(): void
    {
        self::assertSame(
            "<!-- wp:button -->\n"
            . '<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="https://example.test">Go</a></div>'
            . "\n<!-- /wp:button -->",
            (new Button('Go', 'https://example.test'))->render(),
        );
    }

    public function testButtonEscapesUrlAttribute(): void
    {
        $rendered = (new Button('Go', 'https://example.test/?q="x"'))->render();

        self::assertStringContainsString('href="https://example.test/?q=&quot;x&quot;"', $rendered);
    }

    public function testSeparatorRenders(): void
    {
        self::assertSame(
            "<!-- wp:separator -->\n<hr class=\"wp-block-separator has-alpha-channel-opacity\"/>\n<!-- /wp:separator -->",
            (new Separator())->render(),
        );
    }

    public function testPageBreakRenders(): void
    {
        self::assertSame(
            "<!-- wp:nextpage -->\n<!--nextpage-->\n<!-- /wp:nextpage -->",
            (new PageBreak())->render(),
        );
    }

    public function testContainersWrapInnerBlocks(): void
    {
        $columns = new Columns();
        $column = new Column();
        $column->addBlock(new Paragraph('Cell'));
        $columns->addBlock($column);

        $rendered = $columns->render();

        self::assertSame(
            "<!-- wp:columns -->\n"
            . '<div class="wp-block-columns">'
            . "<!-- wp:column -->\n<div class=\"wp-block-column\">"
            . "<!-- wp:paragraph -->\n<p>Cell</p>\n<!-- /wp:paragraph -->"
            . "</div>\n<!-- /wp:column -->"
            . "</div>\n<!-- /wp:columns -->",
            $rendered,
        );
        self::assertSame([$column], $columns->getInnerBlocks());
    }

    public function testButtonsContainerRenders(): void
    {
        $buttons = new Buttons();
        $buttons->addBlock(new Button('A', 'https://a.test'));

        self::assertSame(
            "<!-- wp:buttons -->\n"
            . '<div class="wp-block-buttons">'
            . "<!-- wp:button -->\n"
            . '<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="https://a.test">A</a></div>'
            . "\n<!-- /wp:button -->"
            . "</div>\n<!-- /wp:buttons -->",
            $buttons->render(),
        );
    }

    public function testGroupUsesTagName(): void
    {
        $group = new Group('section');
        $group->addBlock(new Paragraph('In group'));

        self::assertSame(
            "<!-- wp:group {\"tagName\":\"section\"} -->\n"
            . '<section class="wp-block-group">'
            . "<!-- wp:paragraph -->\n<p>In group</p>\n<!-- /wp:paragraph -->"
            . "</section>\n<!-- /wp:group -->",
            $group->render(),
        );
    }

    public function testGroupRejectsUnsupportedTagName(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Group('script');
    }

    public function testBlockRejectsAttributesThatCannotBeEncoded(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new GenericBlock('my-plugin/widget', ['invalid' => NAN]))->render();
    }

    public function testGenericBlockPassesThrough(): void
    {
        $block = new GenericBlock('my-plugin/widget', ['a' => 1], 'inner');

        self::assertSame(
            "<!-- wp:my-plugin/widget {\"a\":1} -->\ninner\n<!-- /wp:my-plugin/widget -->",
            $block->render(),
        );
    }

    public function testEmptyContentBlocksSelfClose(): void
    {
        self::assertSame(
            '<!-- wp:read-more /-->',
            (new ReadMoreButton())->render(),
        );
        self::assertSame(
            '<!-- wp:my-plugin/widget /-->',
            (new GenericBlock('my-plugin/widget'))->render(),
        );
    }
}
