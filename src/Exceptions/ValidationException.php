<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Exceptions;

use Throwable;

/** HTTP 422 — WordPress `rest_invalid_param` with the per-field `params` map. */
final class ValidationException extends WordPressApiException
{
    /**
     * @param array<string, mixed> $params WordPress `data.params` map (field => error)
     */
    public function __construct(
        public readonly array $params,
        string $message = 'Validation failed',
        int $code = 422,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, [
            'code' => 'rest_invalid_param',
            'data' => ['params' => $params],
        ], $previous);
    }
}
