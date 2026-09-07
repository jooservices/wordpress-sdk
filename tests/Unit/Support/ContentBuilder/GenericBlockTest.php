<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Support\ContentBuilder;

use InvalidArgumentException;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\GenericBlock;
use JOOservices\WordPress\Sdk\Tests\TestCase;

final class GenericBlockTest extends TestCase
{
    public function testPassesContentThrough(): void
    {
        $inner = $this->faker->word();
        self::assertSame(
            "<!-- wp:my-plugin/widget {\"a\":1} -->\n{$inner}\n<!-- /wp:my-plugin/widget -->",
            (new GenericBlock('my-plugin/widget', ['a' => 1], $inner))->render(),
        );
    }

    public function testSelfClosesWithoutContent(): void
    {
        self::assertSame('<!-- wp:my-plugin/widget /-->', (new GenericBlock('my-plugin/widget'))->render());
    }

    public function testRejectsAttributesThatCannotBeEncoded(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new GenericBlock('my-plugin/widget', ['invalid' => NAN]))->render();
    }
}
