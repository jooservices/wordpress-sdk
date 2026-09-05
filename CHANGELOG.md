# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [4.0.0] - 2026-09-04

### Added

- Ground-up rebuild of the WordPress REST API SDK on PHP 8.5.
- Single immutable `Config` value object (base URL normalization, timeouts,
  optional `RetryConfig`, optional PSR-3 logger).
- `WordPressService::create()` and `WordPressService::fromConfig()` entry
  points; registry-based lazy service instantiation.
- `Endpoint` string-backed enum (single source of truth for REST paths).
- Hydration through `jooservices/dto` `from()` engine — nested DTOs, casting,
  unknown-key tolerance out of the box.
- `MediaService::update()` (public) and multipart uploads via
  `jooservices/client` `RequestBuilder::withMultipart()`.
- HTTP status → typed exception mapping with sensitive-value redaction in
  `WordPressApiException::toArray()`.
- `all()`, `cursor()`, `each()` pagination helpers on every collection
  service through a shared abstract base (`AbstractCollectionService`).
- Gutenberg `ContentBuilder` with per-instance block registry and a
  round-trip-safe `BlockParser` (valid `wp:…` markup, no `core/` prefix bugs).
- Unit test strategy through `jooservices/client` fakes
  (`ClientBuilder::fake()`, `HttpFakeRegistry`) — no HTTP mocks.
- First-class WordPress 7.1 core route-family support for autosaves, block and
  directory patterns, pattern category terms, fonts/faces/collections, icons,
  abilities, editor support routes, batch requests, and oEmbed.
- Disposable Docker + WP-CLI E2E with live route completeness, authentication,
  content/taxonomy/media/comment/autosave CRUD, and batch mutation coverage.
- Self-hosted GitHub Actions gates for validation, Pint, PHPStan, unit
  coverage, clean-consumer installation, live WordPress E2E, dependency
  review, Composer audit, Gitleaks, Semgrep, CodeQL, and workflow auditing.
- `FontsService::uploadFace()` for multipart font-face uploads (JSON
  `createFace()` unchanged).

### Changed

- **Breaking:** removed `ContainerFactory` and the PHP-DI dependency —
  construct `WordPressService` directly or use `create()`/`fromConfig()`.
- **Breaking:** removed Symfony Serializer/Validator/PropertyInfo dependencies
  — hydration and validation now use `jooservices/dto`.
- **Breaking:** `create()` signature — `(baseUrl, username, password, timeout,
  connectTimeout, retry, logger)`; `maxRetries` became `RetryConfig`.
- **Breaking:** all DTO constructors accept fully defaulted named parameters
  (previously `Post`/`Page` required 23 positional arguments).
- `ValidationException` carries the WordPress `params` map instead of Symfony
  constraint violations.
- Raw CRUD services consolidated on one `RawCrud` trait (`int|string` ids);
  `RawCrudById`/`RawCrudByStringId` removed.
- Removed dead contracts (`HydratorInterface`, `AuthenticatedUserInterface`,
  `RevisionableInterface`, `ServiceInterface`), the unused
  `AppPasswordAuthenticator`, and the dead `maxRetries` option.
- Updated every direct and transitive Composer dependency and removed
  workspace-only repositories from package metadata.
- Pinned the PHP, Composer, PCOV, WordPress, WP-CLI, and MariaDB container
  inputs; Docker bind mounts run with the invoking runner UID/GID.
- `WordPressApiException` (and HTTP subclasses) now extend
  `jooservices/exceptions` `AbstractContextAwareException`, expose stable
  `errorCode()` values (`wordpress.http.*`), and remain catchable via
  `JOOExceptionInterface`. Typed REST subclasses are unchanged.

### Removed

- `Support\ContentBuilder\Blocks\Plugins\TableOfContents` (plugin-specific).
- `BlockPatternInterface` / `applyPattern()` (no demonstrated need).

### Deprecated

- None.

### Security

- Credential redaction hardened in `WordPressApiException::toArray()`.
- Authenticated plain HTTP is rejected by default, as are malformed base URLs,
  non-positive timeouts, password-only credentials, and REST path traversal.
- Response-decoding logs contain only safe metadata and content hashes, never
  response bodies or exception messages.
- Gutenberg block attributes are escaped; invalid JSON/attributes fail closed.

### Fixed

- Nested same-name Gutenberg block parsing and self-closing attribute parsing.
- Pagination E2E coverage for non-public post statuses.
- Treat `Endpoint` as the REST path SSOT (editor / oEmbed / site-health leaves,
  `withChild()` for nested subresources).
- Hydrate `Settings` through `jooservices/dto` `from()` instead of `new Settings()`.
- Align revisions and autosaves on a shared `PostBackedResources` allowlist.
- Tighten `CoreRouteSupport` to SDK-covered route patterns (unknown subroutes
  under known resources fail the gate).
- Declare `nyholm/psr7` as a direct runtime dependency for `Psr17Factory`.
- Allow `Post::$featured_media` to be `null` (WordPress omits featured images).
- Hydrate edit-context `User` fields and `Status::$slug` without cast failures.
- Map HTTP 409 to `ConflictException`; keep the full WordPress payload on
  `ValidationException` (not only the `params` map).
- Percent-encode string path keys via `Endpoint::withKey()` /
  `AbstractStringKeyService::get()`.
- Preserve inline HTML when parsing Gutenberg leaves (`BlockParser` no longer
  `strip_tags()` rich text).
- Append `Status::$slug` after existing constructor parameters to keep
  positional callers compatible.
- Keep `ValidationException` positional args (`$previous`, `$context`) stable;
  pass full payloads via named `data:`.
- Default `ValidationException` payload when a 422 body is empty/`{}`.
- Send WordPress `font_face_settings` JSON (with `src: ["file"]`) from
  `FontsService::uploadFace()`.
- Render multi-paragraph quotes as separate `<p>` elements.

[Unreleased]: https://github.com/jooservices/wordpress-sdk/compare/v4.0.0...HEAD
[4.0.0]: https://github.com/jooservices/wordpress-sdk/releases/tag/v4.0.0
