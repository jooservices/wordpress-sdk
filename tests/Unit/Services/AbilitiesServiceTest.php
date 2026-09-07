<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Services;

use JOOservices\Client\Testing\TestResponse;
use JOOservices\Client\Testing\TestResponseSequence;
use JOOservices\WordPress\Sdk\Tests\TestCase;

final class AbilitiesServiceTest extends TestCase
{
    public function testListsGetsRunsAndReadsCategories(): void
    {
        $abilities = $this->wordPress()->abilities();
        foreach ([
            ['GET', 'wp-abilities/v1/abilities', 'list', [['category' => 'core']]],
            ['GET', 'wp-abilities/v1/abilities/core/get-info', 'get', ['core/get-info']],
            ['POST', 'wp-abilities/v1/abilities/core%2Fget-info/run', 'run', ['core/get-info', ['id' => 1]]],
            ['GET', 'wp-abilities/v1/categories', 'categories', []],
            ['GET', 'wp-abilities/v1/categories/core', 'category', ['core']],
        ] as [$verb, $path, $method, $arguments]) {
            $sequence = new TestResponseSequence();
            $sequence->push(TestResponse::json(['ok' => true]));
            $this->httpFakes()->respond($verb, '*' . $path . '*', $sequence);
            self::assertSame(['ok' => true], $abilities->{$method}(...$arguments));
        }
    }
}
