# Upgrade to 4.0

Version 4.0 is a ground-up replacement for the archived 1.x SDK. Upgrade in a
branch and treat the namespace-compatible package name as a breaking API
change.

## Requirements

- PHP `^8.5`
- `jooservices/client ^4.2`
- `jooservices/dto ^3.2`
- WordPress application passwords over HTTPS for built-in authentication

```bash
composer require jooservices/wordpress-sdk:^4.0 --with-all-dependencies
```

## Required code changes

1. Replace container/PHP-DI construction with `WordPressService::create()` or
   `WordPressService::fromConfig()`.
2. Replace `maxRetries` with a `jooservices/client` `RetryConfig`.
3. Replace Symfony serializer/validator integrations with DTO `from()`
   hydration and `ValidationException::$params`.
4. Convert positional DTO construction to named, optional arguments where
   DTOs are constructed directly.
5. Remove calls to `TableOfContents`, `BlockPatternInterface`, and
   `applyPattern()`; these plugin/editorial helpers are outside SDK scope.
6. Use `RawCrud`-based service methods for raw route groups and
   `MediaService::update()` for media metadata.

## Authentication behavior

Authenticated plain HTTP now throws unless `allowInsecureHttp: true` is set.
That override is for isolated local tests only. Verify production base URLs
use HTTPS and move credentials to environment-backed secret storage.

## Verification

Run the application test suite, then smoke-test read, create, update, delete,
pagination, media upload, and expected exception handling against a staging
WordPress site. Check custom middleware against the PSR-18 client boundary.

## Rollback

Revert the application dependency and code migration together. Do not leave a
v1 integration running with v4 constructor or exception assumptions. A
Composer lockfile rollback should be reviewed for security advisories before
deployment.
