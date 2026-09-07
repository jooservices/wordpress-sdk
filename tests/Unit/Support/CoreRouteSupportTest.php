<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Support;

use JOOservices\WordPress\Sdk\Tests\TestCase;
use JOOservices\WordPress\Sdk\Tests\Support\CoreRouteSupport;

final class CoreRouteSupportTest extends TestCase
{
    public function testRejectsUnknownRouteFamilies(): void
    {
        $routes = [
            '/', '/batch/v1', '/oembed/1.0/embed', '/wp/v2/posts',
            '/wp/v2/posts/(?P<id>[\\d]+)', '/wp-site-health/v1/tests/page-cache',
            '/wp-block-editor/v1/export', '/wp-abilities/v1/abilities',
            '/plugin/v1/items', '/wp/v2/future-resource',
        ];

        self::assertSame(
            ['/plugin/v1/items', '/wp/v2/future-resource'],
            (new CoreRouteSupport())->unsupported($routes),
        );
    }

    public function testAcceptsTemplateAndGlobalStyleRoutes(): void
    {
        $routes = [
            '/wp/v2/templates/(?P<id>([^\\/:<>\\*?"\\|]+(?:\\/[^\\/:<>\\*?"\\|]+)?)[\\/\\w%-]+)',
            '/wp/v2/global-styles/(?P<parent>[\\d]+)/revisions/(?P<id>[\\d]+)',
        ];

        self::assertSame([], (new CoreRouteSupport())->unsupported($routes));
    }
}
