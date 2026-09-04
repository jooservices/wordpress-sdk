<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Support;

use JOOservices\WordPress\Sdk\Endpoints\Endpoint;

/**
 * Audits a WordPress discovery document against routes the SDK actually
 * exposes (Endpoint cases + known nested subresources). Plugin and theme
 * namespaces remain available through CustomEndpointService and are
 * intentionally outside this core gate.
 *
 * Matching is pattern-based after named-capture segments are normalized to
 * `*`, so a new WP sub-route under a known resource fails until the SDK
 * adds an Endpoint/helper for it.
 */
final class CoreRouteSupport
{
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
        $normalized = $this->normalizeDiscoveryRoute($route);

        foreach ($this->coveredPatterns() as $pattern) {
            if ($this->matches($normalized, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private function coveredPatterns(): array
    {
        $patterns = [
            '/',
            '/wp/v2',
        ];

        foreach (Endpoint::cases() as $endpoint) {
            $patterns[] = '/' . $endpoint->path();
            // Namespace roots stay exact — a new sibling under the root must
            // get its own Endpoint case before the gate goes green again.
            if (str_ends_with($endpoint->name, '_ROOT')) {
                continue;
            }

            $patterns[] = '/' . $endpoint->path() . '/*';
        }

        foreach (PostBackedResources::WITH_REVISIONS as $resource) {
            $base = '/' . PostBackedResources::endpoint($resource)->path();
            $patterns[] = $base . '/*/revisions';
            $patterns[] = $base . '/*/revisions/*';
        }

        foreach (PostBackedResources::WITH_AUTOSAVES as $resource) {
            $base = '/' . PostBackedResources::endpoint($resource)->path();
            $patterns[] = $base . '/*/autosaves';
            $patterns[] = $base . '/*/autosaves/*';
        }

        $patterns[] = '/' . Endpoint::MEDIA->path() . '/*/post-process';
        $patterns[] = '/' . Endpoint::MEDIA->path() . '/*/edit';
        $patterns[] = '/' . Endpoint::USERS->path() . '/*/application-passwords';
        $patterns[] = '/' . Endpoint::USERS->path() . '/*/application-passwords/*';
        $patterns[] = '/' . Endpoint::USERS->path() . '/*/application-passwords/introspect';
        $patterns[] = '/' . Endpoint::USERS_ME->path() . '/application-passwords';
        $patterns[] = '/' . Endpoint::USERS_ME->path() . '/application-passwords/*';
        $patterns[] = '/' . Endpoint::USERS_ME->path() . '/application-passwords/introspect';
        $patterns[] = '/' . Endpoint::FONT_FAMILIES->path() . '/*/font-faces';
        $patterns[] = '/' . Endpoint::FONT_FAMILIES->path() . '/*/font-faces/*';
        $patterns[] = '/' . Endpoint::TEMPLATES->path() . '/lookup';
        $patterns[] = '/' . Endpoint::GLOBAL_STYLES->path() . '/themes/*';
        $patterns[] = '/' . Endpoint::GLOBAL_STYLES->path() . '/themes/*/variations';
        $patterns[] = '/' . Endpoint::WIDGET_TYPES->path() . '/*/encode';
        $patterns[] = '/' . Endpoint::WIDGET_TYPES->path() . '/*/render';
        $patterns[] = '/' . Endpoint::ABILITIES->path() . '/*/run';
        $patterns[] = '/' . Endpoint::ABILITIES->path() . '/*/*/run';
        $patterns[] = '/' . Endpoint::ABILITIES->path() . '/*/*';
        $patterns[] = '/' . Endpoint::ICONS->path() . '/*/*';
        $patterns[] = '/' . Endpoint::BLOCK_TYPES->path() . '/*/*';
        $patterns[] = '/' . Endpoint::BLOCK_RENDERER->path() . '/*/*';
        $patterns[] = '/' . Endpoint::PLUGINS->path() . '/*/*';

        return array_values(array_unique($patterns));
    }

    private function normalizeDiscoveryRoute(string $route): string
    {
        $normalized = $this->replaceNamedCaptures($route);
        $normalized = preg_replace('#/+#', '/', $normalized) ?? $normalized;

        if ($normalized !== '/' && str_ends_with($normalized, '/')) {
            $normalized = rtrim($normalized, '/');
        }

        return $normalized;
    }

    /**
     * Replace `(?P<name>…)` segments with `*`, including nested parentheses
     * used by WordPress template / template-part id patterns.
     */
    private function replaceNamedCaptures(string $route): string
    {
        $normalized = '';
        $length = strlen($route);
        $offset = 0;

        while ($offset < $length) {
            $start = strpos($route, '(?P<', $offset);
            if ($start === false) {
                $normalized .= substr($route, $offset);

                break;
            }

            $normalized .= substr($route, $offset, $start - $offset);
            $nameEnd = strpos($route, '>', $start + 4);
            if ($nameEnd === false) {
                $normalized .= substr($route, $start);

                break;
            }

            $depth = 0;
            $cursor = $start;
            $closed = false;
            while ($cursor < $length) {
                $char = $route[$cursor];
                if ($char === '(') {
                    $depth++;
                } elseif ($char === ')') {
                    $depth--;
                    if ($depth === 0) {
                        $normalized .= '*';
                        $offset = $cursor + 1;
                        $closed = true;

                        break;
                    }
                }
                $cursor++;
            }

            if (! $closed) {
                $normalized .= substr($route, $start);

                break;
            }
        }

        return $normalized;
    }

    private function matches(string $route, string $pattern): bool
    {
        if ($route === $pattern) {
            return true;
        }

        $routeParts = $route === '/' ? [''] : explode('/', trim($route, '/'));
        $patternParts = $pattern === '/' ? [''] : explode('/', trim($pattern, '/'));

        if (count($routeParts) !== count($patternParts)) {
            return false;
        }

        foreach ($patternParts as $index => $part) {
            if ($part === '*') {
                continue;
            }

            if (($routeParts[$index] ?? null) !== $part) {
                return false;
            }
        }

        return true;
    }
}
