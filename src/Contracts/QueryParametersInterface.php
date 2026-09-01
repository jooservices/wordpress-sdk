<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Contracts;

/**
 * A value object that can be converted into WordPress REST query parameters.
 */
interface QueryParametersInterface
{
    /**
     * @return array<string, mixed>
     */
    public function toQuery(): array;
}
