<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Support;

use InvalidArgumentException;
use JOOservices\WordPress\Sdk\Endpoints\Endpoint;

/**
 * Shared allowlists for core REST resources that expose revisions and/or
 * autosaves.
 */
final class PostBackedResources
{
    /**
     * Resources with both revisions and autosaves (post-backed editor types).
     *
     * @var list<string>
     */
    public const NAMES = [
        'posts',
        'pages',
        'blocks',
        'templates',
        'template-parts',
        'navigation',
        'menu-items',
    ];

    /**
     * Resources that expose revisions (includes global styles).
     *
     * @var list<string>
     */
    public const WITH_REVISIONS = [
        'posts',
        'pages',
        'blocks',
        'templates',
        'template-parts',
        'navigation',
        'menu-items',
        'global-styles',
    ];

    /**
     * @var list<string>
     */
    public const WITH_AUTOSAVES = self::NAMES;

    public static function assertSupported(string $resource, string $feature): void
    {
        $allowlist = match ($feature) {
            'revision' => self::WITH_REVISIONS,
            'autosave' => self::WITH_AUTOSAVES,
            default => self::NAMES,
        };

        if (! in_array($resource, $allowlist, true)) {
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
            'global-styles' => Endpoint::GLOBAL_STYLES,
            default => throw new InvalidArgumentException('Unsupported post-backed resource: ' . $resource),
        };
    }

    public static function childPath(string $resource, int|string $parentId, string $child): string
    {
        return self::endpoint($resource)->value . '/' . rawurlencode((string) $parentId) . '/' . $child;
    }
}
