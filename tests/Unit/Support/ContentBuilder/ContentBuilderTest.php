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
        $title = $this->faker->sentence();
        $body = $this->faker->paragraph();
        $builder = (new ContentBuilder())
            ->heading($title)
            ->text($body);

        self::assertSame(
            "<!-- wp:heading -->\n<h2>{$title}</h2>\n<!-- /wp:heading -->\n\n"
            . "<!-- wp:paragraph -->\n<p>{$body}</p>\n<!-- /wp:paragraph -->",
            $builder->render(),
        );
    }

    public function testRenderRawJoinsInnerHtml(): void
    {
        $title = $this->faker->sentence();
        $body = $this->faker->paragraph();
        $builder = (new ContentBuilder())->heading($title)->text($body);

        self::assertSame("<h2>{$title}</h2>\n\n<p>{$body}</p>", $builder->renderRaw());
    }

    public function testHtmlAndBlockHelpers(): void
    {
        $raw = $this->faker->word();
        $inner = $this->faker->word();
        $builder = (new ContentBuilder())
            ->html("<div>{$raw}</div>")
            ->block('my-plugin/widget', ['size' => 2], $inner);

        self::assertSame(
            "<!-- wp:html -->\n<div>{$raw}</div>\n<!-- /wp:html -->\n\n"
            . "<!-- wp:my-plugin/widget {\"size\":2} -->\n{$inner}\n<!-- /wp:my-plugin/widget -->",
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

    public function testCodeAndShortcodeHelpers(): void
    {
        $code = $this->faker->word();
        $builder = (new ContentBuilder())
            ->code($code)
            ->shortcode('[gallery]');

        self::assertStringContainsString('wp:code', $builder->render());
        self::assertStringContainsString($code, $builder->render());
        self::assertStringContainsString('wp:shortcode', $builder->render());
        self::assertStringContainsString('[gallery]', $builder->render());
    }

    public function testQuoteWithCitation(): void
    {
        $quote = $this->faker->sentence();
        $citation = $this->faker->name();
        $builder = (new ContentBuilder())->quote($quote, $citation);

        self::assertSame(
            "<!-- wp:quote -->\n<blockquote class=\"wp-block-quote\"><p>{$quote}</p><cite>{$citation}</cite></blockquote>\n<!-- /wp:quote -->",
            $builder->render(),
        );
    }

    public function testColumnsAndGroup(): void
    {
        $left = $this->faker->word();
        $right = $this->faker->word();
        $button = $this->faker->word();
        $url = $this->faker->url();
        $builder = (new ContentBuilder())
            ->columns([
                static function (ContentBuilder $column) use ($left): void {
                    $column->text($left);
                },
                static function (ContentBuilder $column) use ($right): void {
                    $column->text($right);
                },
            ])
            ->group(static function (ContentBuilder $group) use ($button, $url): void {
                $group->button($button, $url);
            });

        $rendered = $builder->render();

        self::assertStringContainsString('<!-- wp:columns -->', $rendered);
        self::assertStringContainsString('<!-- wp:column -->', $rendered);
        self::assertStringContainsString("<p>{$left}</p>", $rendered);
        self::assertStringContainsString("<p>{$right}</p>", $rendered);
        self::assertStringContainsString('<!-- wp:group', $rendered);
        self::assertStringContainsString('<!-- wp:button -->', $rendered);
    }

    public function testButtonsHelperAcceptsArraysAndInstances(): void
    {
        $firstText = $this->faker->word();
        $firstUrl = $this->faker->url();
        $secondText = $this->faker->word();
        $secondUrl = $this->faker->url();
        $builder = (new ContentBuilder())->buttons([
            ['text' => $firstText, 'url' => $firstUrl],
            new Button($secondText, $secondUrl),
        ]);

        $rendered = $builder->render();

        self::assertStringContainsString('<!-- wp:buttons -->', $rendered);
        self::assertStringContainsString('<!-- wp:button -->', $rendered);
        self::assertStringContainsString('href="' . $firstUrl . '"', $rendered);
        self::assertStringContainsString('>' . $secondText . '</a>', $rendered);
    }

    public function testImageFromFileUploadsAndBuildsBlock(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'sdk-img');
        file_put_contents($file, $this->faker->word());
        $sourceUrl = $this->faker->imageUrl();
        $altText = $this->faker->sentence(3);

        try {
            $sequence = new TestResponseSequence();
            $sequence->push(TestResponse::json([
                'id' => 9,
                'source_url' => $sourceUrl,
                'alt_text' => $this->faker->word(),
            ], 201));
            $this->httpFakes()->respond('POST', '*wp/v2/media*', $sequence);

            $wordPress = $this->wordPress();
            $builder = $wordPress->contentBuilder()->imageFromFile($file, ['alt_text' => $altText]);

            self::assertSame(
                "<!-- wp:image {\"id\":9,\"sizeSlug\":\"full\",\"linkDestination\":\"none\"} -->\n"
                . "<figure class=\"wp-block-image size-full\"><img src=\"{$sourceUrl}\" alt=\"{$altText}\" class=\"wp-image-9\"/></figure>\n"
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
        $content = $this->faker->sentence();
        $builder = ContentBuilder::fromHtml("<p>{$content}</p>");

        self::assertSame(
            "<!-- wp:html -->\n<p>{$content}</p>\n<!-- /wp:html -->",
            $builder->render(),
        );
    }

    public function testParseRoundTripsMarkup(): void
    {
        $heading = $this->faker->word();
        $paragraph = $this->faker->sentence();
        $readMore = $this->faker->sentence(2);
        $source = (new ContentBuilder())
            ->heading($heading, 3, ['anchor' => 'h'])
            ->text($paragraph)
            ->readMoreButton($readMore);

        $parsed = ContentBuilder::parse($source->render());

        self::assertSame($source->render(), $parsed->render());
    }

    public function testParseUsesProvidedRegistry(): void
    {
        $registry = new BlockRegistry();
        $content = $this->faker->word();

        $parsed = ContentBuilder::parse("<!-- wp:paragraph -->\n<p>{$content}</p>\n<!-- /wp:paragraph -->", $registry);

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
        file_put_contents($file, $this->faker->word());

        try {
            $sequence = new TestResponseSequence();
            $sequence->push(TestResponse::json([
                'id' => 3,
                'source_url' => $this->faker->imageUrl(),
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
