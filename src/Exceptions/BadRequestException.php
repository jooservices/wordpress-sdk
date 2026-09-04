<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Exceptions;

use JOOservices\Exceptions\Support\ExceptionContext;

/** HTTP 400 — WordPress REST bad request. */
final class BadRequestException extends WordPressApiException
{
    public function errorCode(): string
    {
        return 'wordpress.http.badrequest';
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
