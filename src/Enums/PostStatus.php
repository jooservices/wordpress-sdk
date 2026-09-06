<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Enums;

/**
 * Core post statuses accepted by `/wp/v2/posts`.
 */
enum PostStatus: string
{
    case Publish = 'publish';
    case Future = 'future';
    case Draft = 'draft';
    case Pending = 'pending';
    case Private = 'private';
}
