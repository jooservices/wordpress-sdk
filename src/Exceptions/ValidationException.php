<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Exceptions;

use JOOservices\Exceptions\Support\ExceptionContext;
use Throwable;

/** HTTP 422 / `rest_invalid_param` — per-field `params` map plus full WP payload. */
final class ValidationException extends WordPressApiException
{
    /**
     * @param array<string, mixed> $params WordPress `data.params` map (field => error)
     * @param array<string, mixed>|null $data Full WordPress error payload when available
     */
    public function __construct(
        public readonly array $params,
        string $message = 'Validation failed',
        int $code = 422,
        ?Throwable $previous = null,
        ?ExceptionContext $context = null,
        ?array $data = null,
    ) {
        parent::__construct(
            $message,
            $code,
            $data ?? [
                'code' => 'rest_invalid_param',
                'message' => $message,
                'data' => [
                    'status' => $code,
                    'params' => $params,
                ],
            ],
            $previous,
            $context,
        );
    }

    public function errorCode(): string
    {
        return 'wordpress.http.validation';
    }

    protected function copyWithContext(ExceptionContext $context): static
    {
        return new self(
            $this->params,
            $this->getMessage(),
            $this->getCode(),
            $this->getPrevious(),
            $context,
            $this->data,
        );
    }
}
