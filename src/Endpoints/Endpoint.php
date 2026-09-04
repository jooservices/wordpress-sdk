<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Endpoints;

/**
 * Single source of truth for WordPress REST API paths.
 *
 * Values are relative to the configured REST root (the SDK appends
 * `/wp-json/` to the site base URL) and never carry a leading slash or
 * embedded query strings.
 *
 * Nested sub-resources that require a parent id (revisions, autosaves,
 * font-faces, application-passwords, ability run, media edit helpers)
 * are built via the helpers below so callers never hard-code path stems.
 */
enum Endpoint: string
{
    case POSTS = 'wp/v2/posts';
    case PAGES = 'wp/v2/pages';
    case MEDIA = 'wp/v2/media';
    case COMMENTS = 'wp/v2/comments';
    case CATEGORIES = 'wp/v2/categories';
    case TAGS = 'wp/v2/tags';
    case SEARCH = 'wp/v2/search';
    case TAXONOMIES = 'wp/v2/taxonomies';
    case POST_TYPES = 'wp/v2/types';
    case STATUSES = 'wp/v2/statuses';
    case USERS = 'wp/v2/users';
    case USERS_ME = 'wp/v2/users/me';
    case SETTINGS = 'wp/v2/settings';
    case PLUGINS = 'wp/v2/plugins';
    case THEMES = 'wp/v2/themes';
    case BLOCKS = 'wp/v2/blocks';
    case BLOCK_TYPES = 'wp/v2/block-types';
    case BLOCK_RENDERER = 'wp/v2/block-renderer';
    case BLOCK_DIRECTORY = 'wp/v2/block-directory/search';
    case BLOCK_DIRECTORY_ROOT = 'wp/v2/block-directory';
    case MENU_LOCATIONS = 'wp/v2/menu-locations';
    case NAVIGATIONS = 'wp/v2/navigation';
    case NAV_MENUS = 'wp/v2/menus';
    case NAV_MENU_ITEMS = 'wp/v2/menu-items';
    case TEMPLATES = 'wp/v2/templates';
    case TEMPLATE_PARTS = 'wp/v2/template-parts';
    case GLOBAL_STYLES = 'wp/v2/global-styles';
    case WIDGETS = 'wp/v2/widgets';
    case WIDGET_TYPES = 'wp/v2/widget-types';
    case SIDEBARS = 'wp/v2/sidebars';
    case SITE_HEALTH = 'wp-site-health/v1/tests';
    case SITE_HEALTH_DIRECTORY_SIZES = 'wp-site-health/v1/directory-sizes';
    case SITE_HEALTH_ROOT = 'wp-site-health/v1';
    case BATCH = 'batch/v1';
    case OEMBED_ROOT = 'oembed/1.0';
    case OEMBED_EMBED = 'oembed/1.0/embed';
    case OEMBED_PROXY = 'oembed/1.0/proxy';
    case PATTERN_CATEGORIES = 'wp/v2/wp_pattern_category';
    case PATTERN_DIRECTORY = 'wp/v2/pattern-directory/patterns';
    case PATTERN_DIRECTORY_ROOT = 'wp/v2/pattern-directory';
    case BLOCK_PATTERNS = 'wp/v2/block-patterns/patterns';
    case BLOCK_PATTERN_CATEGORIES = 'wp/v2/block-patterns/categories';
    case BLOCK_PATTERNS_ROOT = 'wp/v2/block-patterns';
    case FONT_FAMILIES = 'wp/v2/font-families';
    case FONT_COLLECTIONS = 'wp/v2/font-collections';
    case ICONS = 'wp/v2/icons';
    case ICON_COLLECTIONS = 'wp/v2/icon-collections';
    case ABILITIES = 'wp-abilities/v1/abilities';
    case ABILITY_CATEGORIES = 'wp-abilities/v1/categories';
    case ABILITIES_ROOT = 'wp-abilities/v1';
    case EDITOR_URL_DETAILS = 'wp-block-editor/v1/url-details';
    case EDITOR_EXPORT = 'wp-block-editor/v1/export';
    case EDITOR_NAVIGATION_FALLBACK = 'wp-block-editor/v1/navigation-fallback';
    case EDITOR_ROOT = 'wp-block-editor/v1';
    case VIEW_CONFIG = 'wp/v2/view-config';

    public function path(): string
    {
        return $this->value;
    }

    public function withId(int|string $id): string
    {
        return $this->value . '/' . $id;
    }

    public function withKey(string $key): string
    {
        return $this->value . '/' . $key;
    }

    /**
     * @param list<int|string> $values
     */
    public function withValues(array $values): string
    {
        return $this->value . '/' . implode('/', array_map(
            static fn(int|string $value): string => rawurlencode((string) $value),
            $values,
        ));
    }

    /**
     * Nested child under an item id (revisions, autosaves, font-faces, …).
     */
    public function withChild(int|string $id, string $child): string
    {
        return $this->withId($id) . '/' . $child;
    }
}
