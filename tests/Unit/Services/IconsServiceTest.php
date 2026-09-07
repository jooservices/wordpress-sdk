<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Services;

use JOOservices\Client\Testing\TestResponse;
use JOOservices\Client\Testing\TestResponseSequence;
use JOOservices\WordPress\Sdk\Tests\TestCase;

final class IconsServiceTest extends TestCase
{
    public function testReadsIconsAndCollections(): void
    {
        $icons = $this->wordPress()->icons();
        foreach ([
            ['wp/v2/icons', 'list', []],
            ['wp/v2/icons/core', 'list', ['core']],
            ['wp/v2/icons/core/home', 'get', ['core', 'home']],
            ['wp/v2/icon-collections', 'collections', []],
            ['wp/v2/icon-collections/core', 'collection', ['core']],
        ] as [$path, $method, $arguments]) {
            $sequence = new TestResponseSequence();
            $sequence->push(TestResponse::json(['ok' => true]));
            $this->httpFakes()->respond('GET', '*' . $path . '*', $sequence);
            self::assertSame(['ok' => true], $icons->{$method}(...$arguments));
        }
    }
}
