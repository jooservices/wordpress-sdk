# jooservices/wordpress-sdk

This file adds project-only rules.

- PHP `^8.5`; runtime deps: `jooservices/client ^4.2`, `jooservices/dto ^3.2`, `jooservices/exceptions ^4.0`, `nyholm/psr7 ^1.8`, `psr/log ^3.0`
- First public line: **`v4.0.0`** — ground-up rebuild of the archived v1.x SDK
- All PHP tooling via Docker (`php:8.5-cli-bookworm`), `make <target>` (install/lint/test/ci)
- Lints at **max**: Pint `per` preset + PHPStan max on `src/` and `tests/` (phpstan-phpunit)
- Coverage gate: >= 90% aggregate; zero-covered files/classes/methods rejected (see `tools/test-coverage-gate.php`)
- **Scope boundary**: SDK only. No content templates, no editorial workflows, no plugin-specific helpers. Never re-add `TableOfContents`, `BlockPatternInterface`, PHP-DI, or Symfony Serializer.
- **Architecture invariants** (see `knowledge.md` §7):
  - Services type against PSR-18 `ClientInterface`; requests built with `jooservices/client` `RequestBuilder` (never an SDK-owned request builder)
  - Hydration exclusively via `jooservices/dto` `from()` — DTOs are readonly, every constructor param defaulted, nullable for WP-omittable fields
  - One `Endpoint` enum; one `AbstractCrudService`; one `RawCrud` trait; dead contracts never re-added
  - Tests exercise the real request path via `ClientBuilder::fake()` / `HttpFakeRegistry` — no NullHttpClient-style doubles
- Unit test files mirror `src/` paths under `tests/Unit/`
- Branch model: `develop` for integration, `master` for production, tags from `master`
- IDE: Pint format-on-save — use `tools/pint` (Docker wrapper) or `make lint:fix`
