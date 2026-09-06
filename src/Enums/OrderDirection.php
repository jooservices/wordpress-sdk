<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Enums;

/**
 * WordPress collection `order` query values.
 */
enum OrderDirection: string
{
    case Asc = 'asc';
    case Desc = 'desc';
}
