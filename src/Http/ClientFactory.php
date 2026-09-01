<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Http;

use JOOservices\Client\Client\ClientBuilder;
use JOOservices\Client\Resilience\RetryConfig;
use JOOservices\WordPress\Sdk\Config;
use Psr\Http\Client\ClientInterface;

/**
 * Builds the PSR-18 HTTP client from the SDK configuration.
 *
 * Wiring:
 * - base URI is the normalized REST root (relative SDK paths resolve against it)
 * - Basic auth for WordPress application passwords (username/password)
 * - optional retry policy (defaults to the client RetryConfig — never POSTs)
 * - optional PSR-3 logger
 * - `Accept: application/json`
 */
final class ClientFactory
{
    public function create(Config $config): ClientInterface
    {
        $builder = ClientBuilder::create()
            ->withBaseUri($config->baseUrl)
            ->withHeader('Accept', 'application/json')
            ->withRetry($config->retry ?? new RetryConfig())
            ->withGeneratedUserAgent('jooservices/wordpress-sdk');

        if ($config->timeout !== Config::DEFAULT_TIMEOUT) {
            $builder = $builder->withTimeout($config->timeout);
        }

        if ($config->connectTimeout !== Config::DEFAULT_CONNECT_TIMEOUT) {
            $builder = $builder->withConnectTimeout($config->connectTimeout);
        }

        if ($config->username !== '') {
            $builder = $builder->withBasicAuth($config->username, $config->password);
        }

        if ($config->logger !== null) {
            $builder = $builder->withLogger($config->logger);
        }

        return $builder->build();
    }
}
