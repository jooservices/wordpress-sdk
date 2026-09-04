<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Exceptions;

use JOOservices\Exceptions\Support\ExceptionContext;

/** HTTP 429 — WordPress REST rate limit. */
final class RateLimitException extends WordPressApiException
{
    public function errorCode(): string
    {
        return 'wordpress.http.ratelimit';
    }

    protected function copyWithContext(ExceptionContext $context): static
    {
        return new self(
            $this->getMessage(),
            $this->getCode(),
            $this->data,
            $this->getPrevious(),
            $context,
        );
    }
}
