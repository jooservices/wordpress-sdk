<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk;

use InvalidArgumentException;
use JOOservices\Client\Resilience\RetryConfig;
use Psr\Log\LoggerInterface;

/**
 * Immutable SDK configuration.
 *
 * The base URL is normalized to the WordPress REST root: a site URL such as
 * `https://example.com` becomes `https://example.com/wp-json/`. A URL that
 * already ends with `/wp-json` is kept (trailing slash added).
 */
final readonly class Config
{
    public const DEFAULT_TIMEOUT = 30.0;

    public const DEFAULT_CONNECT_TIMEOUT = 10.0;

    public string $baseUrl;

    public function __construct(
        string $baseUrl,
        public string $username = '',
        public string $password = '',
        public float $timeout = self::DEFAULT_TIMEOUT,
        public float $connectTimeout = self::DEFAULT_CONNECT_TIMEOUT,
        public ?RetryConfig $retry = null,
        public ?LoggerInterface $logger = null,
        public bool $allowInsecureHttp = false,
    ) {
        $this->baseUrl = self::normalizeBaseUrl($baseUrl);

        if ($timeout <= 0.0) {
            throw new InvalidArgumentException('Timeout must be greater than zero.');
        }

        if ($connectTimeout <= 0.0) {
            throw new InvalidArgumentException('Connect timeout must be greater than zero.');
        }

        if ($password !== '' && $username === '') {
            throw new InvalidArgumentException('A username is required when a password is provided.');
        }

        if ($username !== '' && parse_url($this->baseUrl, PHP_URL_SCHEME) !== 'https' && ! $allowInsecureHttp) {
            throw new InvalidArgumentException(
                'Authenticated WordPress connections require HTTPS. '
                . 'Set allowInsecureHttp only for an isolated local test environment.',
            );
        }
    }

    private static function normalizeBaseUrl(string $baseUrl): string
    {
        if ($baseUrl === '' || filter_var($baseUrl, FILTER_VALIDATE_URL) === false) {
            throw new InvalidArgumentException(sprintf('Invalid base URL: %s', $baseUrl));
        }

        $parts = parse_url($baseUrl);
        $scheme = $parts['scheme'] ?? null;

        if (! is_array($parts)
            || ! in_array($scheme, ['http', 'https'], true)
            || ! isset($parts['host'])
            || isset($parts['query'])
            || isset($parts['fragment'])
            || isset($parts['user'])
            || isset($parts['pass'])) {
            throw new InvalidArgumentException(
                'Base URL must be an HTTP(S) origin or site path without credentials, query, or fragment.',
            );
        }

        $baseUrl = rtrim($baseUrl, '/');

        if (! str_ends_with($baseUrl, '/wp-json')) {
            $baseUrl .= '/wp-json';
        }

        return $baseUrl . '/';
    }
}
