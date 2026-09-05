<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit;

use InvalidArgumentException;
use JOOservices\Client\Resilience\RetryConfig;
use JOOservices\WordPress\Sdk\Config;
use JOOservices\WordPress\Sdk\Tests\TestCase;

final class ConfigTest extends TestCase
{
    public function testNormalizesSiteUrlToRestRoot(): void
    {
        $config = new Config('https://example.com');

        self::assertSame('https://example.com/wp-json/', $config->baseUrl);
    }

    public function testKeepsExistingRestRootAndAddsTrailingSlash(): void
    {
        $config = new Config('https://example.com/wp-json');

        self::assertSame('https://example.com/wp-json/', $config->baseUrl);
    }

    public function testKeepsRestRootWithTrailingSlash(): void
    {
        $config = new Config('https://example.com/wp-json/');

        self::assertSame('https://example.com/wp-json/', $config->baseUrl);
    }

    public function testTrimsTrailingSlashFromSiteUrl(): void
    {
        $config = new Config('https://example.com/');

        self::assertSame('https://example.com/wp-json/', $config->baseUrl);
    }

    public function testDefaults(): void
    {
        $config = new Config('https://example.com');

        self::assertSame('', $config->username);
        self::assertSame('', $config->password);
        self::assertSame(30.0, $config->timeout);
        self::assertSame(10.0, $config->connectTimeout);
        self::assertNull($config->retry);
        self::assertNull($config->logger);
    }

    public function testCustomValues(): void
    {
        $retry = new RetryConfig(maxAttempts: 2);

        $config = new Config('https://example.com', 'user', 'pass', 15.0, 5.0, $retry);

        self::assertSame('user', $config->username);
        self::assertSame('pass', $config->password);
        self::assertSame(15.0, $config->timeout);
        self::assertSame(5.0, $config->connectTimeout);
        self::assertSame($retry, $config->retry);
    }

    public function testRejectsInvalidBaseUrl(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Config('not a url');
    }

    public function testRejectsEmptyBaseUrl(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Config('');
    }

    public function testRejectsNegativeTimeout(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Config('https://example.com', timeout: -1.0);
    }

    public function testRejectsZeroTimeout(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Config('https://example.com', timeout: 0.0);
    }

    public function testRejectsNegativeConnectTimeout(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Config('https://example.com', connectTimeout: -1.0);
    }

    public function testRejectsZeroConnectTimeout(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Config('https://example.com', connectTimeout: 0.0);
    }

    public function testRejectsUrlQueryFragmentAndEmbeddedCredentials(): void
    {
        foreach ([
            'https://example.com?rest_route=/wp/v2/posts',
            'https://example.com/#fragment',
            'https://user:pass@example.com',
        ] as $url) {
            try {
                new Config($url);
                self::fail(sprintf('Expected URL to be rejected: %s', $url));
            } catch (InvalidArgumentException) {
                self::addToAssertionCount(1);
            }
        }
    }

    public function testRejectsPasswordWithoutUsername(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Config('https://example.com', password: 'secret');
    }

    public function testRejectsAuthenticatedHttpByDefault(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Config('http://example.com', username: 'admin', password: 'secret');
    }

    public function testAllowsExplicitInsecureHttpForIsolatedTests(): void
    {
        $config = new Config(
            'http://wordpress.test',
            username: 'admin',
            password: 'secret',
            allowInsecureHttp: true,
        );

        self::assertSame('http://wordpress.test/wp-json/', $config->baseUrl);
    }
}
