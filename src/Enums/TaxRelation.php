<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Enums;

/**
 * Relationship between multiple taxonomy filters.
 */
enum TaxRelation: string
{
    case And = 'AND';
    case Or = 'OR';
}
