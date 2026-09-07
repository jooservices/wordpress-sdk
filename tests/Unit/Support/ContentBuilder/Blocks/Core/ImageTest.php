<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Support\ContentBuilder\Blocks\Core;

use JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core\Image;
use JOOservices\WordPress\Sdk\Tests\TestCase;

final class ImageTest extends TestCase
{
    public function testRendersWithDefaults(): void
    {
        $url = $this->faker->imageUrl();
        $alt = $this->faker->word();
        self::assertSame(
            "<!-- wp:image {\"id\":4,\"sizeSlug\":\"full\",\"linkDestination\":\"none\"} -->\n"
            . "<figure class=\"wp-block-image size-full\"><img src=\"{$url}\" alt=\"{$alt}\" class=\"wp-image-4\"/></figure>\n"
            . "<!-- /wp:image -->",
            (new Image(4, $url, $alt))->render(),
        );
    }

    public function testEscapesAttributeValues(): void
    {
        $rendered = (new Image(4, 'https://example.test/a.png?x="y"', 'A "quote"'))->render();
        self::assertStringContainsString('src="https://example.test/a.png?x=&quot;y&quot;"', $rendered);
        self::assertStringContainsString('alt="A &quot;quote&quot;"', $rendered);
    }
}
