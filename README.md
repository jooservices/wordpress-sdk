# jooservices/wordpress-sdk

[![CI](https://github.com/jooservices/wordpress-sdk/actions/workflows/ci.yml/badge.svg?branch=develop)](https://github.com/jooservices/wordpress-sdk/actions/workflows/ci.yml)
[![codecov](https://codecov.io/gh/jooservices/wordpress-sdk/graph/badge.svg)](https://codecov.io/gh/jooservices/wordpress-sdk)
[![Quality gate status](https://sonarcloud.io/api/project_badges/measure?project=jooservices_wordpress-sdk&metric=alert_status)](https://sonarcloud.io/summary/new_code?id=jooservices_wordpress-sdk)
[![OpenSSF Scorecard](https://api.securityscorecards.dev/projects/github.com/jooservices/wordpress-sdk/badge)](https://securityscorecards.dev/viewer/?uri=github.com/jooservices/wordpress-sdk)
[![PHP Version](https://img.shields.io/badge/PHP-8.5%2B-blue.svg)](https://www.php.net/)
[![Release](https://img.shields.io/badge/version-4.0.0-blue.svg)](CHANGELOG.md)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)

Strictly typed PHP 8.5 SDK for the WordPress REST API.

Package: `jooservices/wordpress-sdk` · v4.0.0 (ground-up rebuild)

> [!WARNING]
> **`v4.0.0` is a complete rebuild and is not backward compatible with archived `v1.x`.** See [About v4.0.0](#about-v400) and [UPGRADE-4.0.md](UPGRADE-4.0.md).

## About v4.0.0

| | |
| --- | --- |
| Status | **`v4.0.0` — current release** |
| First public line | `v4.0.0` |
| Runtime | PHP `^8.5`, `jooservices/client ^4.2`, `jooservices/dto ^3.2`, `jooservices/exceptions ^4.0` |

## Features

- Typed CRUD for posts, pages, media, comments, users, terms, settings, application passwords
- Generic `resource()` / `terms()` for `show_in_rest` custom post types and taxonomies
- Query DTOs, write payloads, REST enums, pagination (`list` / `cursor` / `each`)
- Discovery, custom endpoints, batch, oEmbed, Gutenberg `ContentBuilder`
- WordPress 7.1 core route-family coverage with a live Docker + WP-CLI E2E gate

## Requirements

- PHP `^8.5`
- `jooservices/client ^4.2`, `jooservices/dto ^3.2`, `jooservices/exceptions ^4.0`

## Installation

```bash
composer require jooservices/wordpress-sdk
```

## Quick start

```php
use JOOservices\WordPress\Sdk\Data\Query\ListPostsQuery;
use JOOservices\WordPress\Sdk\Enums\PostStatus;
use JOOservices\WordPress\Sdk\WordPressService;

$wordpress = WordPressService::create(
    baseUrl: getenv('WORDPRESS_URL'),
    username: getenv('WORDPRESS_USER'),
    password: getenv('WORDPRESS_APP_PASSWORD'),
);

$posts = $wordpress->posts()->list(new ListPostsQuery(
    perPage: 10,
    search: 'automation',
    status: PostStatus::Publish,
    fields: 'id,title,link,date',
));

foreach ($posts as $post) {
    echo $post->title?->rendered . PHP_EOL;
}
```

WordPress application passwords are Basic auth — `create()` wires it. For bearer/JWT, build a `jooservices/client` instance and pass it to `WordPressService::fromClient()`.

Media creates go through `media()->upload()`. JSON `create()` on media is rejected.

Custom post types and taxonomies:

```php
$product = $wordpress->resource('product')->get(12);
$terms = $wordpress->terms('product_cat')->list();
```

## Documentation

- [Knowledge base](./knowledge.md)
- [Getting started](./docs/01-getting-started/installation.md)
- [User guide](./docs/02-user-guide/services-and-queries.md)
- [Coverage matrix](./docs/04-maintenance/coverage-matrix.md)
- [Upgrade from v1.x](./UPGRADE-4.0.md)
- [CI/CD workflows](./WORKFLOWS.md)
- [Changelog](./CHANGELOG.md)

## Development

All PHP tooling runs through Docker:

```bash
make install
make lint
make lint-fix
make test
make test-coverage
make integration
make ci
```

Unit tests use `jooservices/client` HTTP fakes (266 tests, 92.32% statements).
`make integration` provisions disposable WordPress 7.1, MariaDB, and WP-CLI
(3 live tests), then tears them down.

## Community

- [Contributing](./CONTRIBUTING.md)
- [Security](./SECURITY.md)
- [Support](./SUPPORT.md)
- [Code of Conduct](./CODE_OF_CONDUCT.md)
- [Governance](./GOVERNANCE.md)

## License

[MIT](./LICENSE)
