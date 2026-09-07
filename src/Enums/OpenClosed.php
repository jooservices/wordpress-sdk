<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Enums;

/**
 * WordPress `comment_status` / `ping_status` values.
 */
enum OpenClosed: string
{
    case Open = 'open';
    case Closed = 'closed';
}
