<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Support\ContentBuilder;

use JOOservices\Client\Testing\TestResponse;
use JOOservices\Client\Testing\TestResponseSequence;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\BlockRegistry;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core\Button;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\ContentBuilder;
use JOOservices\WordPress\Sdk\Tests\TestCase;
use RuntimeException;

final class ContentBuilderTest extends TestCase
{
    public function testRendersBlocksJoinedByBlankLine(): void
    {
        $builder = (new ContentBuilder())
            ->heading('Title')
            ->text('Body');

        self::assertSame(
            "<!-- wp:heading -->\n<h2>Title</h2>\n<!-- /wp:heading -->\n\n"
            . "<!-- wp:paragraph -->\n<p>Body</p>\n<!-- /wp:paragraph -->",
            $builder->render(),
        );
    }

    public function testRenderRawJoinsInnerHtml(): void
    {
        $builder = (new ContentBuilder())->heading('Title')->text('Body');

        self::assertSame("<h2>Title</h2>\n\n<p>Body</p>", $builder->renderRaw());
    }

    public function testHtmlAndBlockHelpers(): void
    {
        $builder = (new ContentBuilder())
            ->html('<div>raw</div>')
            ->block('my-plugin/widget', ['size' => 2], 'inner');

        self::assertSame(
            "<!-- wp:html -->\n<div>raw</div>\n<!-- /wp:html -->\n\n"
            . "<!-- wp:my-plugin/widget {\"size\":2} -->\ninner\n<!-- /wp:my-plugin/widget -->",
            $builder->render(),
        );
    }

    public function testSelfClosingBlocks(): void
    {
        $builder = (new ContentBuilder())->readMoreButton()->pageBreak()->separator();

        self::assertSame(
            "<!-- wp:read-more /-->\n\n"
            . "<!-- wp:nextpage -->\n<!--nextpage-->\n<!-- /wp:nextpage -->\n\n"
            . "<!-- wp:separator -->\n<hr class=\"wp-block-separator has-alpha-channel-opacity\"/>\n<!-- /wp:separator -->",
            $builder->render(),
        );
    }

    public function testQuoteWithCitation(): void
    {
        $builder = (new ContentBuilder())->quote('To be', 'Shakespeare');

        self::assertSame(
            "<!-- wp:quote -->\n<blockquote class=\"wp-block-quote\"><p>To be</p><cite>Shakespeare</cite></blockquote>\n<!-- /wp:quote -->",
            $builder->render(),
        );
    }

    public function testColumnsAndGroup(): void
    {
        $builder = (new ContentBuilder())
            ->columns([
                static function (ContentBuilder $column): void {
                    $column->text('Left');
                },
                static function (ContentBuilder $column): void {
                    $column->text('Right');
                },
            ])
            ->group(static function (ContentBuilder $group): void {
                $group->button('Read more', 'https://example.com');
            });

        $rendered = $builder->render();

        self::assertStringContainsString('<!-- wp:columns -->', $rendered);
        self::assertStringContainsString('<!-- wp:column -->', $rendered);
        self::assertStringContainsString('<p>Left</p>', $rendered);
        self::assertStringContainsString('<p>Right</p>', $rendered);
        self::assertStringContainsString('<!-- wp:group', $rendered);
        self::assertStringContainsString('<!-- wp:button -->', $rendered);
    }

    public function testButtonsHelperAcceptsArraysAndInstances(): void
    {
        $builder = (new ContentBuilder())->buttons([
            ['text' => 'A', 'url' => 'https://a.test'],
            new Button('B', 'https://b.test'),
        ]);

        $rendered = $builder->render();

        self::assertStringContainsString('<!-- wp:buttons -->', $rendered);
        self::assertStringContainsString('<!-- wp:button -->', $rendered);
        self::assertStringContainsString('href="https://a.test"', $rendered);
        self::assertStringContainsString('>B</a>', $rendered);
    }

    public function testImageFromFileUploadsAndBuildsBlock(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'sdk-img');
        file_put_contents($file, 'img');

        try {
            $sequence = new TestResponseSequence();
            $sequence->push(TestResponse::json([
                'id' => 9,
                'source_url' => 'https://example.test/photo.png',
                'alt_text' => 'Photo',
            ], 201));
            $this->httpFakes()->respond('POST', '*wp/v2/media*', $sequence);

            $wordPress = $this->wordPress();
            $builder = $wordPress->contentBuilder()->imageFromFile($file, ['alt_text' => 'Custom alt']);

            self::assertSame(
                "<!-- wp:image {\"id\":9,\"sizeSlug\":\"full\",\"linkDestination\":\"none\"} -->\n"
                . "<figure class=\"wp-block-image size-full\"><img src=\"https://example.test/photo.png\" alt=\"Custom alt\" class=\"wp-image-9\"/></figure>\n"
                . "<!-- /wp:image -->",
                $builder->render(),
            );
        } finally {
            unlink($file);
        }
    }

    public function testImageFromFileRequiresMediaService(): void
    {
        $builder = new ContentBuilder();

        $this->expectException(RuntimeException::class);

        $builder->imageFromFile('/some/file.png');
    }

    public function testFromHtmlWrapsRawHtml(): void
    {
        $builder = ContentBuilder::fromHtml('<p>Legacy</p>');

        self::assertSame(
            "<!-- wp:html -->\n<p>Legacy</p>\n<!-- /wp:html -->",
            $builder->render(),
        );
    }

    public function testParseRoundTripsMarkup(): void
    {
        $source = (new ContentBuilder())
            ->heading('H', 3, ['anchor' => 'h'])
            ->text('P')
            ->readMoreButton('Continue');

        $parsed = ContentBuilder::parse($source->render());

        self::assertSame($source->render(), $parsed->render());
    }

    public function testParseUsesProvidedRegistry(): void
    {
        $registry = new BlockRegistry();

        $parsed = ContentBuilder::parse("<!-- wp:paragraph -->\n<p>x</p>\n<!-- /wp:paragraph -->", $registry);

        self::assertCount(1, $parsed->getBlocks());
    }

    public function testRegisterBlockRegistersOnBuilderRegistry(): void
    {
        $builder = new ContentBuilder();
        $builder->registerBlock('my-plugin/custom', CustomTestBlock::class);

        self::assertTrue($builder->registry()->has('my-plugin/custom'));
        self::assertSame(CustomTestBlock::class, $builder->registry()->get('my-plugin/custom'));
    }

    public function testMediaServiceIsPropagatedToInnerBuilders(): void
    {
        $wordPress = $this->wordPress();

        $file = tempnam(sys_get_temp_dir(), 'sdk-inner-img');
        file_put_contents($file, 'img');

        try {
            $sequence = new TestResponseSequence();
            $sequence->push(TestResponse::json([
                'id' => 3,
                'source_url' => 'https://example.test/i.png',
                'alt_text' => '',
            ], 201));
            $this->httpFakes()->respond('POST', '*wp/v2/media*', $sequence);

            $builder = $wordPress->contentBuilder()->group(static function (ContentBuilder $group) use ($file): void {
                $group->imageFromFile($file);
            });

            self::assertStringContainsString('<!-- wp:image', $builder->render());
        } finally {
            unlink($file);
        }
    }
}

/**
 * Test block used to verify custom registrations.
 */
final class CustomTestBlock extends \JOOservices\WordPress\Sdk\Support\ContentBuilder\AbstractBlock
{
    protected function getName(): string
    {
        return 'custom';
    }

    protected function getAttributes(): array
    {
        return [];
    }

    protected function getContent(): string
    {
        return 'custom-content';
    }
}
