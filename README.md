# JOOservices WordPress SDK

[![CI](https://github.com/jooservices/wordpress-sdk/actions/workflows/ci.yml/badge.svg?branch=develop)](https://github.com/jooservices/wordpress-sdk/actions/workflows/ci.yml)
[![OpenSSF Scorecard](https://api.securityscorecards.dev/projects/github.com/jooservices/wordpress-sdk/badge)](https://securityscorecards.dev/viewer/?uri=github.com/jooservices/wordpress-sdk)
[![PHP Version](https://img.shields.io/badge/PHP-8.5%2B-blue.svg)](https://www.php.net/)
[![Release](https://img.shields.io/badge/version-4.0.0-blue.svg)](CHANGELOG.md)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)

DTO-first PHP 8.5 SDK for the WordPress REST API — typed content automation,
pagination, media uploads, settings, users, taxonomies, discovery, custom
endpoints, and the complete WordPress 7.1 core REST route families.

Package: `jooservices/wordpress-sdk` · v4.0.0 (ground-up rebuild)

## Install

```bash
composer require jooservices/wordpress-sdk
```

Requires PHP `^8.5`, `jooservices/client ^4.2`, `jooservices/dto ^3.2`,
`jooservices/exceptions ^4.0`.

## Quick start

```php
use JOOservices\WordPress\Sdk\WordPressService;

$wordpress = WordPressService::create(
    baseUrl: getenv('WORDPRESS_URL'),
    username: getenv('WORDPRESS_USER'),
    password: getenv('WORDPRESS_APP_PASSWORD'),
);

$posts = $wordpress->posts()->list(new \JOOservices\WordPress\Sdk\Data\Query\ListPostsQuery(
    perPage: 10,
    search: 'automation',
    fields: 'id,title,link,date',
));

foreach ($posts as $post) {
    echo $post->title?->rendered . PHP_EOL;
}
```

WordPress application passwords are Basic auth — `create()` wires it for you.

## Supported endpoints

| Endpoint | Service | Surface |
| --- | --- | --- |
| Posts | `posts()` | typed CRUD + builder |
| Pages | `pages()` | typed CRUD |
| Media | `media()` | typed CRUD + multipart upload + post-process/edit |
| Users | `users()` | typed CRUD + `me()` |
| Comments | `comments()` | typed CRUD |
| Categories / Tags | `categories()` / `tags()` | typed CRUD |
| Search | `search()` | typed list |
| Taxonomies / Types / Statuses | `taxonomies()` / `postTypes()` / `statuses()` | typed get/list by slug |
| Settings | `settings()` | typed get/update |
| Application passwords | `applicationPasswords()` | typed list/get/create/introspect, raw delete/deleteAll |
| Discovery | `discovery()` | index / routes / schema (OPTIONS) |
| Custom endpoints | `custom()` | GET/POST/PUT/PATCH/DELETE raw arrays |
| Revisions / autosaves | `revisions()` / `autosaves()` | shared allowlist of core post-backed resources (raw) |
| Plugins / Themes | `plugins()` / `themes()` | raw arrays |
| Blocks / Block types / Renderer / Directory | `blocks()` / `blockTypes()` / `blockRenderer()` / `blockDirectory()` | raw arrays |
| Menus / Navigation | `menuLocations()` / `navigations()` / `navMenus()` / `navMenuItems()` | raw arrays |
| Templates / Parts / Global styles | `templates()` / `templateParts()` / `globalStyles()` | raw arrays |
| Widgets / Types / Sidebars | `widgets()` / `widgetTypes()` / `sidebars()` | raw arrays |
| Site Health | `siteHealth()` | raw reads |
| Patterns | `patterns()` | registry, directory, categories, term CRUD |
| Fonts | `fonts()` | family/face CRUD + collections |
| Icons | `icons()` | icons + collections |
| Abilities | `abilities()` | discovery, categories, execution |
| Block editor | `editor()` | URL details, export, navigation fallback, view config |
| Batch / oEmbed | `utility()` | core batch mutations + embed/proxy |

`CoreRouteSupport` audits a live discovery document against **SDK-covered
route patterns** (Endpoint cases + known nested subresources such as
revisions/autosaves/font-faces). The Docker E2E fails when WordPress adds a
new default core route that the SDK has not declared. Plugin and theme
namespaces remain available through `custom()`.

## Typed querying

List/read operations accept typed query DTOs or plain arrays:

```php
use JOOservices\WordPress\Sdk\Data\Query\ListCommentsQuery;

$comments = $wordpress->comments()->list(new ListCommentsQuery(
    post: 42,
    status: 'approve',
    perPage: 25,
    fields: 'id,content,date',
));
```

## Pagination

`list()` returns a `PaginatedCollection` with WordPress totals; every
collection service also streams with `all()`, `cursor()`, and `each()`:

```php
foreach ($wordpress->posts()->cursor(['per_page' => 50]) as $post) {
    // Streams one page at a time.
}

$wordpress->posts()->each(function ($post): bool {
    return $post->id !== 123; // return false to stop early
});
```

Prefer `cursor()`/`each()` over `all()` for large collections.

## Media upload

```php
$media = $wordpress->media()->upload('/tmp/photo.png', ['alt_text' => 'Sunset']);
$wordpress->posts()->update(42, ['featured_media' => $media->id]);
```

## Content builder

`ContentBuilder` generates Gutenberg block markup, and `PostBuilder` assembles
post payloads — both generic SDK helpers, no editorial templates:

```php
$builder = $wordpress->contentBuilder();
$content = $builder
    ->heading('Chapter 1', 3, ['anchor' => 'chapter-1'])
    ->text('Body text')
    ->button('Read more', 'https://example.com')
    ->render();

$post = $wordpress->posts()->builder()
    ->title('Article')
    ->content($content)
    ->status('draft')
    ->create();
```

## Error handling

HTTP errors map to typed exceptions; client-level failures (timeouts, DNS)
propagate from the transport layer:

```php
use JOOservices\Exceptions\Contracts\JOOExceptionInterface;
use JOOservices\WordPress\Sdk\Exceptions\UnauthorizedException;
use JOOservices\WordPress\Sdk\Exceptions\WordPressApiException;

try {
    $wordpress->posts()->get(123, ['context' => 'edit']);
} catch (UnauthorizedException $exception) {
    // Refresh credentials — keep catching typed REST subclasses.
} catch (WordPressApiException $exception) {
    $payload = $exception->toArray(); // sanitized, credentials redacted
} catch (JOOExceptionInterface $exception) {
    // Optional ecosystem-wide catch across JOOservices packages.
}
```

| Status | Exception |
| --- | --- |
| 400 | `BadRequestException` (or `ValidationException` for `rest_invalid_param`) |
| 401 | `UnauthorizedException` |
| 403 | `ForbiddenException` |
| 404 | `NotFoundException` |
| 409 | `ConflictException` |
| 422 | `ValidationException` (`params` map + full WordPress payload) |
| 429 | `RateLimitException` |
| 5xx | `ServerException` |

## Custom clients and configuration

`Config` is a single immutable value object; `fromConfig()` is the advanced
entry point:

```php
use JOOservices\WordPress\Sdk\Config;
use JOOservices\WordPress\Sdk\WordPressService;

$wordpress = WordPressService::fromConfig(new Config(
    baseUrl: 'https://example.com',
    username: 'admin',
    password: 'xxxx xxxx xxxx xxxx',
    timeout: 15.0,
    connectTimeout: 5.0,
));
```

Retries default to the `jooservices/client` policy (GET/HEAD/PUT/DELETE/OPTIONS
on 408/425/429/5xx) and are configurable via `RetryConfig`. A PSR-3 logger can
be injected for decoder diagnostics.

## Scope boundary

This package is the WordPress REST API SDK and nothing more. Editorial content
templates and publishing workflows live in separate packages; this SDK keeps
only the native REST client surface, typed DTOs, query DTOs, pagination,
discovery/schema helpers, error mapping, and the generic payload helpers above.

## Development

All PHP tooling runs through Docker:

```bash
make install   # build the image and composer install
make lint      # pint (PER-CS 3.0) + phpstan (level max)
make test      # phpunit Unit suite (229 tests)
make test-coverage
make integration # disposable WordPress 7.1 + MariaDB + WP-CLI E2E
make clean-consumer # install with published dependencies only
make ci        # lint + coverage gate + composer audit
make hooks-install # optional local CaptainHook installation
```

Unit tests exercise the real request path through `jooservices/client` HTTP
fakes — no network, no mocks of SDK internals. The coverage gate is 90%
aggregate and also rejects every zero-covered production file/class/method;
the current verified result is 96.70% (1144/1183 statements).

Authenticated configuration requires HTTPS. Plain HTTP is rejected unless the
explicit `allowInsecureHttp: true` override is used for an isolated local test
environment such as the disposable Docker E2E.

## Security

Use WordPress application passwords via environment variables. Never commit
live credentials and never log authorization headers. See
[SECURITY.md](./SECURITY.md) for reporting.

## Documentation

- [Knowledge base](./knowledge.md) — archive learnings and v4 design decisions
- [Getting started](./docs/01-getting-started/installation.md)
- [User guide](./docs/02-user-guide/services-and-queries.md)
- [Coverage matrix](./docs/04-maintenance/coverage-matrix.md)
- [Risks and roadmap](./docs/04-maintenance/risks-and-roadmap.md)
- [Upgrade from v1.x](./UPGRADE-4.0.md)
- [CI/CD workflows](./WORKFLOWS.md)
- [Release and rollback](./docs/04-maintenance/release-and-rollback.md)
- [Support](./SUPPORT.md) · [Governance](./GOVERNANCE.md) · [Code of Conduct](./CODE_OF_CONDUCT.md)
