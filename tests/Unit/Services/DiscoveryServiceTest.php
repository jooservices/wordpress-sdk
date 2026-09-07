<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Services;

use JOOservices\Client\Testing\TestResponse;
use JOOservices\Client\Testing\TestResponseSequence;
use JOOservices\WordPress\Sdk\Tests\TestCase;

final class DiscoveryServiceTest extends TestCase
{
    public function testReadsIndexAndRoutes(): void
    {
        $name = $this->faker->word();
        $payload = ['name' => $name, 'namespaces' => ['wp/v2'], 'routes' => ['/wp/v2' => ['namespace' => 'wp/v2']]];
        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::json($payload));
        $sequence->push(TestResponse::json($payload));
        $this->httpFakes()->respond('GET', '*', $sequence);

        self::assertSame($name, $this->wordPress()->discovery()->index()['name']);
        self::assertSame($payload['routes'], $this->wordPress()->discovery()->routes());
        self::assertSame('/wp-json', $this->lastRequest()->getUri()->getPath());
        self::assertSame('', $this->lastRequest()->getUri()->getQuery());
    }

    public function testReturnsEmptyRoutesWhenKeyIsMissing(): void
    {
        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::json(['name' => $this->faker->word()]));
        $this->httpFakes()->respond('GET', '*', $sequence);

        self::assertSame([], $this->wordPress()->discovery()->routes());
    }

    public function testSchemaUsesOptionsVerb(): void
    {
        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::json(['schema' => ['type' => 'object']]));
        $this->httpFakes()->respond('OPTIONS', '*wp/v2/posts*', $sequence);

        self::assertSame(['schema' => ['type' => 'object']], $this->wordPress()->discovery()->schema('wp/v2/posts'));
        self::assertSame('OPTIONS', $this->lastRequest()->getMethod());
        self::assertSame('/wp-json/wp/v2/posts', $this->lastRequest()->getUri()->getPath());
    }
}
