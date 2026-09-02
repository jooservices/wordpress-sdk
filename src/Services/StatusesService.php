<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Services;

use JOOservices\WordPress\Sdk\Data\Status;
use JOOservices\WordPress\Sdk\Endpoints\Endpoint;

/**
 * @extends AbstractStringKeyService<Status>
 */
final class StatusesService extends AbstractStringKeyService
{
    protected function dtoClass(): string
    {
        return Status::class;
    }

    protected function listPath(): string
    {
        return Endpoint::STATUSES->path();
    }
}
