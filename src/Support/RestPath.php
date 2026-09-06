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

    /**
     * Collection path for a custom post type or taxonomy `rest_base`.
     *
     * Bare slugs (`product`) become `wp/v2/product`. Namespaced paths
     * (`wp/v2/product`, `my-plugin/v1/items`) are kept as-is.
     */
    public function collection(string $restBase): string
    {
        $path = $this->normalize($restBase);
        if ($path === '') {
            throw new InvalidArgumentException('REST collection path cannot be empty.');
        }

        if (! str_contains($path, '/')) {
            return 'wp/v2/' . $path;
        }

        return $path;
    }
}
