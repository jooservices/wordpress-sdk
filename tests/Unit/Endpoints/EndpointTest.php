<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Endpoints;

use JOOservices\WordPress\Sdk\Endpoints\Endpoint;
use JOOservices\WordPress\Sdk\Tests\TestCase;

final class EndpointTest extends TestCase
{
    public function testPathIsRelativeWithoutLeadingSlash(): void
    {
        self::assertSame('wp/v2/posts', Endpoint::POSTS->path());
        self::assertSame('wp/v2/users/me', Endpoint::USERS_ME->path());
        self::assertSame('wp-site-health/v1/tests', Endpoint::SITE_HEALTH->path());
    }

    public function testWithId(): void
    {
        self::assertSame('wp/v2/posts/42', Endpoint::POSTS->withId(42));
        self::assertSame('wp/v2/templates/my-template', Endpoint::TEMPLATES->withId('my-template'));
    }

    public function testWithKey(): void
    {
        self::assertSame('wp/v2/taxonomies/category', Endpoint::TAXONOMIES->withKey('category'));
        self::assertSame('wp/v2/themes/theme%2Fchild', Endpoint::THEMES->withKey('theme/child'));
        self::assertSame('wp/v2/statuses/a%20b', Endpoint::STATUSES->withKey('a b'));
    }

    public function testWithValuesEncodesSegments(): void
    {
        self::assertSame('wp/v2/types/post/extra', Endpoint::POST_TYPES->withValues(['post', 'extra']));
        self::assertSame('wp/v2/categories/a%20b', Endpoint::CATEGORIES->withValues(['a b']));
    }

    public function testCoveredEndpoints(): void
    {
        self::assertSame('wp/v2/pages', Endpoint::PAGES->path());
        self::assertSame('wp/v2/media', Endpoint::MEDIA->path());
        self::assertSame('wp/v2/comments', Endpoint::COMMENTS->path());
        self::assertSame('wp/v2/categories', Endpoint::CATEGORIES->path());
        self::assertSame('wp/v2/tags', Endpoint::TAGS->path());
        self::assertSame('wp/v2/search', Endpoint::SEARCH->path());
        self::assertSame('wp/v2/types', Endpoint::POST_TYPES->path());
        self::assertSame('wp/v2/statuses', Endpoint::STATUSES->path());
        self::assertSame('wp/v2/users', Endpoint::USERS->path());
        self::assertSame('wp/v2/settings', Endpoint::SETTINGS->path());
        self::assertSame('wp/v2/plugins', Endpoint::PLUGINS->path());
        self::assertSame('wp/v2/themes', Endpoint::THEMES->path());
        self::assertSame('wp/v2/blocks', Endpoint::BLOCKS->path());
        self::assertSame('wp/v2/block-types', Endpoint::BLOCK_TYPES->path());
        self::assertSame('wp/v2/block-renderer', Endpoint::BLOCK_RENDERER->path());
        self::assertSame('wp/v2/block-directory/search', Endpoint::BLOCK_DIRECTORY->path());
        self::assertSame('wp/v2/menu-locations', Endpoint::MENU_LOCATIONS->path());
        self::assertSame('wp/v2/navigation', Endpoint::NAVIGATIONS->path());
        self::assertSame('wp/v2/menus', Endpoint::NAV_MENUS->path());
        self::assertSame('wp/v2/menu-items', Endpoint::NAV_MENU_ITEMS->path());
        self::assertSame('wp/v2/templates', Endpoint::TEMPLATES->path());
        self::assertSame('wp/v2/template-parts', Endpoint::TEMPLATE_PARTS->path());
        self::assertSame('wp/v2/global-styles', Endpoint::GLOBAL_STYLES->path());
        self::assertSame('wp/v2/widgets', Endpoint::WIDGETS->path());
        self::assertSame('wp/v2/widget-types', Endpoint::WIDGET_TYPES->path());
        self::assertSame('wp/v2/sidebars', Endpoint::SIDEBARS->path());
    }

    public function testWithChildBuildsNestedSubresourcePaths(): void
    {
        self::assertSame('wp/v2/posts/9/revisions', Endpoint::POSTS->withChild(9, 'revisions'));
        self::assertSame(
            'wp/v2/font-families/1/font-faces',
            Endpoint::FONT_FAMILIES->withChild(1, 'font-faces'),
        );
        self::assertSame('wp-block-editor/v1/export', Endpoint::EDITOR_EXPORT->path());
        self::assertSame('wp/v2/view-config', Endpoint::VIEW_CONFIG->path());
        self::assertSame('oembed/1.0/embed', Endpoint::OEMBED_EMBED->path());
        self::assertSame('oembed/1.0', Endpoint::OEMBED_ROOT->path());
    }
}
