<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Exceptions;

use JOOservices\Exceptions\Support\ExceptionContext;

/** HTTP 401 — WordPress REST unauthorized. */
final class UnauthorizedException extends WordPressApiException
{
    public function errorCode(): string
    {
        return 'wordpress.http.unauthorized';
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
