<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Support;

use InvalidArgumentException;
use JOOservices\WordPress\Sdk\Support\RestPath;
use JOOservices\WordPress\Sdk\Tests\TestCase;

final class RestPathTest extends TestCase
{
    private RestPath $restPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->restPath = new RestPath();
    }

    public function testNormalizesPath(): void
    {
        self::assertSame('wp/v2/posts', $this->restPath->normalize('wp/v2/posts'));
        self::assertSame('wp/v2/posts', $this->restPath->normalize('/wp/v2/posts'));
        self::assertSame('wp/v2/posts', $this->restPath->normalize('wp/v2/posts/'));
        self::assertSame('my-plugin/v1/items', $this->restPath->normalize('my-plugin//v1/items/'));
    }

    public function testEmptyPathStaysEmpty(): void
    {
        self::assertSame('', $this->restPath->normalize(''));
        self::assertSame('', $this->restPath->normalize('   '));
    }

    public function testRejectsAbsoluteUrls(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->restPath->normalize('https://example.com/wp-json/wp/v2/posts');
    }

    public function testRejectsProtocolRelativePaths(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->restPath->normalize('//example.com/wp-json/wp/v2/posts');
    }

    public function testRejectsEncodedDotSegments(): void
    {
        foreach ([
            'wp/v2/%2e%2e/users',
            'wp/v2/%252e%252e/users',
            'wp/v2/.%2e/users',
            'wp/v2/%2e%2e%2fusers',
            'wp/v2/%252e%252e%252fusers',
        ] as $path) {
            try {
                $this->restPath->normalize($path);
                self::fail(sprintf('Expected encoded traversal to be rejected: %s', $path));
            } catch (InvalidArgumentException) {
                self::addToAssertionCount(1);
            }
        }
    }

    public function testCollectionPrefixesBareSlugs(): void
    {
        self::assertSame('wp/v2/product', $this->restPath->collection('product'));
        self::assertSame('wp/v2/book', $this->restPath->collection('wp/v2/book'));
        self::assertSame('my-plugin/v1/items', $this->restPath->collection('my-plugin/v1/items'));
    }

    public function testCollectionRejectsEmptyPath(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->restPath->collection('   ');
    }
}
