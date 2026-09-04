<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Support;

use InvalidArgumentException;
use JOOservices\WordPress\Sdk\Endpoints\Endpoint;

/**
 * Shared allowlist for core post-backed REST resources that expose
 * revisions and autosaves.
 */
final class PostBackedResources
{
    /** @var list<string> */
    public const NAMES = [
        'posts',
        'pages',
        'blocks',
        'templates',
        'template-parts',
        'navigation',
        'menu-items',
    ];

    public static function assertSupported(string $resource, string $feature): void
    {
        if (! in_array($resource, self::NAMES, true)) {
            throw new InvalidArgumentException(
                sprintf('Unsupported %s resource: %s', $feature, $resource),
            );
        }
    }

    public static function endpoint(string $resource): Endpoint
    {
        return match ($resource) {
            'posts' => Endpoint::POSTS,
            'pages' => Endpoint::PAGES,
            'blocks' => Endpoint::BLOCKS,
            'templates' => Endpoint::TEMPLATES,
            'template-parts' => Endpoint::TEMPLATE_PARTS,
            'navigation' => Endpoint::NAVIGATIONS,
            'menu-items' => Endpoint::NAV_MENU_ITEMS,
            default => throw new InvalidArgumentException('Unsupported post-backed resource: ' . $resource),
        };
    }

    public static function childPath(string $resource, int|string $parentId, string $child): string
    {
        return self::endpoint($resource)->value . '/' . rawurlencode((string) $parentId) . '/' . $child;
    }
}
