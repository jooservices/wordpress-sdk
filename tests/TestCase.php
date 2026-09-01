<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests;

use JOOservices\Client\Client\ClientBuilder;
use JOOservices\Client\Resilience\RetryConfig;
use JOOservices\Client\Testing\InteractsWithHttpClient;
use JOOservices\WordPress\Sdk\WordPressService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Psr\Http\Message\RequestInterface;

/**
 * Base test case: every test exercises the real request path through the
 * `jooservices/client` HTTP fakes.
 */
abstract class TestCase extends BaseTestCase
{
    use InteractsWithHttpClient;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHttpFakes();
        ClientBuilder::fake($this->httpFakes());
    }

    protected function tearDown(): void
    {
        $this->tearDownHttpFakes();
        parent::tearDown();
    }

    /**
     * A facade wired against fakes, with retries disabled for determinism.
     */
    protected function wordPress(): WordPressService
    {
        return WordPressService::create(
            baseUrl: 'https://example.test',
            username: 'admin',
            password: 'xxxx xxxx xxxx xxxx',
            retry: new RetryConfig(maxAttempts: 1),
        );
    }

    protected function lastRequest(): RequestInterface
    {
        $recorded = $this->httpFakes()->recorded();

        self::assertNotEmpty($recorded, 'No HTTP request was recorded.');

        return end($recorded)->request;
    }

    /**
     * @param array<string, mixed> $expected
     */
    protected function assertQuery(RequestInterface $request, array $expected): void
    {
        parse_str($request->getUri()->getQuery(), $actual);

        foreach ($expected as $key => $value) {
            self::assertArrayHasKey($key, $actual, sprintf('Query key %s missing.', $key));
            $actualValue = $actual[$key];
            self::assertSame(
                is_scalar($value) ? (string) $value : json_encode($value),
                is_scalar($actualValue) ? (string) $actualValue : json_encode($actualValue),
                sprintf('Query key %s mismatch.', $key),
            );
        }
    }

    protected function assertNoQuery(RequestInterface $request): void
    {
        self::assertSame('', $request->getUri()->getQuery());
    }

    /**
     * @param array<string, mixed> $expected
     */
    protected function assertJsonBody(RequestInterface $request, array $expected): void
    {
        $decoded = json_decode((string) $request->getBody(), true);

        self::assertIsArray($decoded);
        ksort($expected);
        ksort($decoded);
        self::assertSame($expected, $decoded);
    }
}
