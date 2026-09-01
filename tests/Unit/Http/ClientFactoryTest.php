<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Http;

use JOOservices\Client\Resilience\RetryConfig;
use JOOservices\Client\Testing\TestResponse;
use JOOservices\Client\Testing\TestResponseSequence;
use JOOservices\WordPress\Sdk\Config;
use JOOservices\WordPress\Sdk\Http\ClientFactory;
use JOOservices\WordPress\Sdk\Tests\TestCase;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseInterface;

final class ClientFactoryTest extends TestCase
{
    public function testAppliesBasicAuthWhenUsernameProvided(): void
    {
        $client = (new ClientFactory())->create(new Config('https://example.com', 'admin', 'secret'));

        $this->send($client, 'GET', 'wp/v2/users/me');

        $request = $this->lastRequest();
        self::assertSame('Basic ' . base64_encode('admin:secret'), $request->getHeaderLine('Authorization'));
    }

    public function testOmitsAuthHeaderWithoutUsername(): void
    {
        $client = (new ClientFactory())->create(new Config('https://example.com'));

        $this->send($client, 'GET', 'wp/v2/settings');

        self::assertSame('', $this->lastRequest()->getHeaderLine('Authorization'));
    }

    public function testSendsAcceptHeaderAndResolvesRelativePaths(): void
    {
        $client = (new ClientFactory())->create(new Config('https://example.com'));

        $this->send($client, 'GET', 'wp/v2/posts');

        $request = $this->lastRequest();
        self::assertSame('application/json', $request->getHeaderLine('Accept'));
        self::assertSame('https://example.com/wp-json/wp/v2/posts', (string) $request->getUri());
    }

    public function testResolvesRestRootWithoutTrailingSlash(): void
    {
        $client = (new ClientFactory())->create(new Config('https://example.com/wp-json'));

        $this->send($client, 'GET', 'wp/v2/posts');

        self::assertSame('https://example.com/wp-json/wp/v2/posts', (string) $this->lastRequest()->getUri());
    }

    public function testRetryIsAppliedByDefault(): void
    {
        $client = (new ClientFactory())->create(new Config('https://example.com'));

        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::make(500, [], '{"code":"internal"}'));
        $sequence->push(TestResponse::make(500, [], '{"code":"internal"}'));
        $sequence->push(TestResponse::make(200, [], '{}'));
        $this->httpFakes()->respond('GET', '*wp/v2/posts*', $sequence);

        $response = $client->sendRequest((new Psr17Factory())->createRequest('GET', 'wp/v2/posts'));

        self::assertSame(200, $response->getStatusCode());
        self::assertCount(3, $this->httpFakes()->recorded());
    }

    public function testRetryConfigurable(): void
    {
        $client = (new ClientFactory())->create(new Config(
            'https://example.com',
            retry: new RetryConfig(maxAttempts: 1),
        ));

        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::make(500, [], '{}'));
        $this->httpFakes()->respond('GET', '*wp/v2/posts*', $sequence);

        $response = $client->sendRequest((new Psr17Factory())->createRequest('GET', 'wp/v2/posts'));

        self::assertSame(500, $response->getStatusCode());
        self::assertCount(1, $this->httpFakes()->recorded());
    }

    private function send(ClientInterface $client, string $method, string $uri): ResponseInterface
    {
        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::make(200, [], '{}'));
        $this->httpFakes()->respond($method, '*', $sequence);

        return $client->sendRequest((new Psr17Factory())->createRequest($method, $uri));
    }
}
