<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Enums;

/**
 * WordPress REST `context` query values.
 */
enum RestContext: string
{
    case View = 'view';
    case Embed = 'embed';
    case Edit = 'edit';
}
