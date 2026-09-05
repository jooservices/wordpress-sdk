<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Exceptions;

use JOOservices\Exceptions\Support\ExceptionContext;

/** HTTP 409 — WordPress REST conflict (e.g. duplicate slug / locked resource). */
final class ConflictException extends WordPressApiException
{
    public function errorCode(): string
    {
        return 'wordpress.http.conflict';
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
