<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Support;

/**
 * Audits a WordPress discovery document against the core route families that
 * have first-class SDK support. Plugin and theme namespaces remain available
 * through CustomEndpointService and are intentionally outside this core gate.
 */
final class CoreRouteSupport
{
    private const WP_V2_RESOURCES = [
        'posts', 'pages', 'media', 'menu-items', 'blocks', 'templates', 'template-parts',
        'global-styles', 'navigation', 'font-families', 'types', 'statuses', 'taxonomies',
        'categories', 'tags', 'menus', 'wp_pattern_category', 'users', 'comments', 'search',
        'block-renderer', 'block-types', 'settings', 'themes', 'plugins', 'sidebars',
        'widget-types', 'widgets', 'block-directory', 'pattern-directory', 'block-patterns',
        'menu-locations', 'font-collections', 'icons', 'icon-collections', 'view-config',
    ];

    /**
     * @param list<string> $routes
     * @return list<string>
     */
    public function unsupported(array $routes): array
    {
        $unsupported = [];

        foreach ($routes as $route) {
            if ($this->supports($route)) {
                continue;
            }

            $unsupported[] = $route;
        }

        return $unsupported;
    }

    private function supports(string $route): bool
    {
        if (in_array($route, ['/', '/batch/v1', '/oembed/1.0'], true)) {
            return true;
        }

        if (str_starts_with($route, '/oembed/1.0/')) {
            return true;
        }

        foreach (['/wp-site-health/v1', '/wp-block-editor/v1', '/wp-abilities/v1'] as $namespace) {
            if ($route === $namespace || str_starts_with($route, $namespace . '/')) {
                return true;
            }
        }

        if ($route === '/wp/v2') {
            return true;
        }

        if (! str_starts_with($route, '/wp/v2/')) {
            return false;
        }

        $resource = explode('/', substr($route, strlen('/wp/v2/')), 2)[0];

        return in_array($resource, self::WP_V2_RESOURCES, true);
    }
}
