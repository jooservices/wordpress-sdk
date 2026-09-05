<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Exceptions;

use JOOservices\Exceptions\Support\ExceptionContext;

/** HTTP 5xx — WordPress REST server error. */
final class ServerException extends WordPressApiException
{
    public function errorCode(): string
    {
        return 'wordpress.http.servererror';
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
