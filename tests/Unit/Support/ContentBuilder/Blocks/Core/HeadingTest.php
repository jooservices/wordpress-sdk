<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Support\ContentBuilder\Blocks\Core;

use InvalidArgumentException;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core\Heading;
use JOOservices\WordPress\Sdk\Tests\TestCase;

final class HeadingTest extends TestCase
{
    public function testSerializesOnlyNonDefaultLevel(): void
    {
        $title = $this->faker->sentence();
        self::assertSame("<!-- wp:heading -->\n<h2>{$title}</h2>\n<!-- /wp:heading -->", (new Heading($title))->render());
        self::assertSame(
            "<!-- wp:heading {\"level\":3} -->\n<h3>{$title}</h3>\n<!-- /wp:heading -->",
            (new Heading($title, 3))->render(),
        );
    }

    public function testRejectsInvalidLevels(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Heading($this->faker->sentence(), 7);
    }
}
