<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Services;

use JOOservices\Client\Testing\TestResponse;
use JOOservices\Client\Testing\TestResponseSequence;
use JOOservices\WordPress\Sdk\Tests\TestCase;

final class UtilityServiceTest extends TestCase
{
    public function testCallsBatchAndOembedRoutes(): void
    {
        $utility = $this->wordPress()->utility();
        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::json(['responses' => []]));
        $this->httpFakes()->respond('POST', '*batch/v1*', $sequence);
        self::assertSame(['responses' => []], $utility->batch([
            ['method' => 'POST', 'path' => '/wp/v2/posts', 'body' => ['title' => $this->faker->sentence()]],
        ], 'require-all-validate'));

        $html = sprintf('<p>%s</p>', $this->faker->sentence());
        $url = $this->faker->url();
        foreach (['embed' => [[$url, ['maxwidth' => 600]], 'oembed/1.0/embed'], 'proxy' => [[$url], 'oembed/1.0/proxy']] as $method => [$arguments, $path]) {
            $sequence = new TestResponseSequence();
            $sequence->push(TestResponse::json(['html' => $html]));
            $this->httpFakes()->respond('GET', '*' . $path . '*', $sequence);
            self::assertSame(['html' => $html], $utility->{$method}(...$arguments));
        }
    }
}
