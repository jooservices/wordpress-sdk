<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Support\ContentBuilder;

use InvalidArgumentException;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\GenericBlock;
use JOOservices\WordPress\Sdk\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

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

    public function testSerializesBareCoreBlockName(): void
    {
        self::assertSame('<!-- wp:paragraph /-->', (new GenericBlock('paragraph'))->render());
    }

    public function testEscapesCommentTerminatorInAttributes(): void
    {
        $payload = '--> <script>alert(1)</script>';
        $rendered = (new GenericBlock('paragraph', ['payload' => $payload]))->render();

        self::assertSame(
            '<!-- wp:paragraph {"payload":"--\\u003E \\u003Cscript\\u003Ealert(1)\\u003C/script\\u003E"} /-->',
            $rendered,
        );
        self::assertStringNotContainsString('--> <script>', $rendered);
    }

    #[DataProvider('invalidBlockNames')]
    public function testRejectsInvalidBlockNames(string $name): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new GenericBlock($name))->render();
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidBlockNames(): iterable
    {
        yield 'comment terminator' => ['custom/block --> <script>'];
        yield 'extra namespace separator' => ['custom/block/extra'];
        yield 'uppercase name' => ['Custom/block'];
        yield 'reserved core namespace prefix' => ['core/paragraph'];
    }

    public function testRejectsAttributesThatCannotBeEncoded(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new GenericBlock('my-plugin/widget', ['invalid' => NAN]))->render();
    }
}
