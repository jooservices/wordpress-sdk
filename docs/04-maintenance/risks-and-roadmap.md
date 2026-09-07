# Risks and roadmap

## Scope discipline

The SDK covers the native WordPress REST API surface only. Editorial content
templates and publishing workflows belong to separate packages that depend on
this SDK. If a helper starts depending on one plugin or theme contract, it is
a maintenance candidate for extraction — not an SDK feature.

## Operational notes

- `all()` materializes every page in memory — prefer `cursor()`/`each()` on
  large sites.
- Raw admin/editor groups intentionally return arrays until their response
  schemas prove stable enough for public DTOs.
- Retries (default `RetryConfig`) never replay POST bodies; multipart uploads
  are retried only if the underlying file stream is still open.
- `make integration` provisions disposable pinned WordPress 7.1, MariaDB, and
  WP-CLI containers, installs a site, creates an application password, runs
  live workflows, and removes containers and volumes.

## Roadmap

1. **Conditional requests** — ETag/Last-Modified helpers.
2. **Typed revisions** — add a public `Revision` DTO if the cross-resource
   response schema becomes stable enough; raw scopes are already complete.
3. **Content builder** — per-block parser fidelity for custom registered
   classes; container `tagName` handling on non-Group containers.
