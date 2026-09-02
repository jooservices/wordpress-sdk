<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Support;

use InvalidArgumentException;

/**
 * Normalizes user-supplied REST paths.
 *
 * Paths must be relative to the configured WordPress REST root: absolute
 * URLs and protocol-relative paths are rejected.
 */
final class RestPath
{
    public function normalize(string $path): string
    {
        $path = trim($path);

        if ($path === '') {
            return '';
        }

        if (filter_var($path, FILTER_VALIDATE_URL) !== false || str_starts_with($path, '//')) {
            throw new InvalidArgumentException(
                'REST paths must be relative to the configured WordPress REST API root.',
            );
        }

        $collapsed = preg_replace('#/+#', '/', $path) ?? '/';

        foreach (explode('/', $collapsed) as $segment) {
            if ($segment === '.' || $segment === '..') {
                throw new InvalidArgumentException('REST paths cannot contain dot segments.');
            }
        }

        return trim($collapsed, '/');
    }
}
