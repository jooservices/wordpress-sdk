<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Services;

use JOOservices\Client\Testing\TestResponse;
use JOOservices\Client\Testing\TestResponseSequence;
use JOOservices\WordPress\Sdk\Tests\TestCase;

final class EditorServiceTest extends TestCase
{
    public function testCallsEditorRoutes(): void
    {
        foreach ([
            ['urlDetails', 'wp-block-editor/v1/url-details', [$this->faker->url()]],
            ['export', 'wp-block-editor/v1/export', []],
            ['navigationFallback', 'wp-block-editor/v1/navigation-fallback', []],
            ['viewConfig', 'wp/v2/view-config', []],
        ] as [$method, $path, $arguments]) {
            $sequence = new TestResponseSequence();
            $sequence->push(TestResponse::json(['ok' => true]));
            $this->httpFakes()->respond('GET', '*' . $path . '*', $sequence);
            self::assertSame(['ok' => true], $this->wordPress()->editor()->{$method}(...$arguments));
        }
    }
}
