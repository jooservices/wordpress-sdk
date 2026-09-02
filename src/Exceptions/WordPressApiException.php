<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Base exception for every WordPress REST API failure.
 *
 * @phpstan-type WordPressErrorPayload array<string, mixed>
 */
class WordPressApiException extends RuntimeException
{
    /**
     * @param array<string, mixed>|null $data raw WordPress error payload
     */
    public function __construct(
        string $message,
        int $code = 0,
        public readonly ?array $data = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Structured, sanitized diagnostic payload suitable for logging or
     * error responses. Credential-like values are redacted recursively.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => static::class,
            'message' => $this->getMessage(),
            'status_code' => $this->code !== 0 ? $this->code : null,
            'wordpress_code' => isset($this->data['code']) && is_string($this->data['code'])
                ? $this->data['code']
                : null,
            'wordpress_data' => isset($this->data['data'])
                ? self::sanitize($this->data['data'])
                : null,
            'response' => $this->data !== null ? self::sanitize($this->data) : null,
            'previous' => $this->getPrevious() !== null ? $this->getPrevious()::class : null,
        ];
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    private static function sanitize(mixed $value, int $depth = 0): mixed
    {
        if ($depth > 8) {
            return '(depth limit)';
        }

        if (is_array($value)) {
            $result = [];
            foreach ($value as $key => $item) {
                if (is_string($key) && self::looksSensitiveKey($key)) {
                    $result[$key] = '(redacted)';
                    continue;
                }

                $result[$key] = self::sanitize($item, $depth + 1);
            }

            return $result;
        }

        if (is_string($value) && self::looksSensitiveValue($value)) {
            return '(redacted)';
        }

        return $value;
    }

    private static function looksSensitiveKey(string $key): bool
    {
        $normalized = str_replace(['-', '_'], '', strtolower($key));

        foreach (self::SENSITIVE_KEYS as $sensitive) {
            if (str_contains($normalized, $sensitive)) {
                return true;
            }
        }

        return false;
    }

    private static function looksSensitiveValue(string $value): bool
    {
        if (preg_match('/^(?:Basic|Bearer)\s/i', $value) === 1) {
            return true;
        }

        return preg_match('/[A-Za-z0-9]{4}\s+[A-Za-z0-9]{4}\s+[A-Za-z0-9]{4}/', $value) === 1;
    }

    /**
     * @var list<string>
     */
    private const SENSITIVE_KEYS = [
        'authorization',
        'password',
        'application_password',
        'app_password',
        'token',
        'secret',
        'cookie',
        'set-cookie',
    ];
}
