<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Exceptions;

use JOOservices\Exceptions\Base\AbstractJOORuntimeException;
use JOOservices\Exceptions\Concerns\HasExceptionContext;
use JOOservices\Exceptions\Contracts\ContextAwareExceptionInterface;
use JOOservices\Exceptions\Contracts\LoggableExceptionInterface;
use JOOservices\Exceptions\Support\CompositeContextRedactor;
use JOOservices\Exceptions\Support\ExceptionContext;
use Throwable;

/**
 * Base exception for every WordPress REST API failure.
 *
 * Uses the shared JOOservices exception contracts so callers can catch
 * {@see \JOOservices\Exceptions\Contracts\JOOExceptionInterface} across
 * packages. HTTP semantics stay in the typed subclasses
 * ({@see UnauthorizedException}, {@see NotFoundException}, …) — do not collapse
 * those into a single generic exception type.
 *
 * @phpstan-type WordPressErrorPayload array<string, mixed>
 */
class WordPressApiException extends AbstractJOORuntimeException implements
    ContextAwareExceptionInterface,
    LoggableExceptionInterface
{
    use HasExceptionContext;

    private static bool $redactorConfigured = false;

    /**
     * @param array<string, mixed>|null $data raw WordPress error payload
     */
    public function __construct(
        string $message,
        int $code = 0,
        public readonly ?array $data = null,
        ?Throwable $previous = null,
        ?ExceptionContext $context = null,
    ) {
        self::configureRedactor();
        parent::__construct($message, $code, $previous);
        $this->initContext(
            $context !== null
                ? $context->toArray()
                : self::contextPayload($code, $data),
        );
    }

    public function errorCode(): string
    {
        return 'wordpress.http.apierror';
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
            'error_code' => $this->errorCode(),
        ];
    }

    protected function copyWithContext(ExceptionContext $context): static
    {
        /** @var static $copy */
        $copy = new self(
            $this->getMessage(),
            $this->getCode(),
            $this->data,
            $this->getPrevious(),
            $context,
        );

        return $copy;
    }

    /**
     * @param array<string, mixed>|null $data
     *
     * @return array<string, mixed>
     */
    private static function contextPayload(int $code, ?array $data): array
    {
        /** @var array<string, mixed> $payload */
        $payload = array_filter(
            [
                'status_code' => $code !== 0 ? $code : null,
                'wordpress_code' => isset($data['code']) && is_string($data['code'])
                    ? $data['code']
                    : null,
                'response' => $data,
            ],
            static fn(mixed $value): bool => $value !== null,
        );

        return $payload;
    }

    private static function configureRedactor(): void
    {
        if (self::$redactorConfigured) {
            return;
        }

        // Shared process redactor used by HasExceptionContext::getContext().
        \JOOservices\Exceptions\Base\AbstractContextAwareException::setRedactor(
            CompositeContextRedactor::withExtraKeys([
                'application_password',
                'app_password',
                'wordpress_application_password',
            ]),
        );
        self::$redactorConfigured = true;
    }

    /**
     * @param mixed $value
     *
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
