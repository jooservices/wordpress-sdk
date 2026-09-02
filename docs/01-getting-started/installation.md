# Installation

## Requirements

- PHP `^8.5`
- `jooservices/client ^4.2` (PSR-18 client — installed automatically)
- `jooservices/dto ^3.2` (hydration engine — installed automatically)

## Install

```bash
composer require jooservices/wordpress-sdk
```

## Configuration

Nothing to configure at install time. Credentials are passed to
`WordPressService::create()` at runtime (see the quick start) — keep them in
environment variables, never in code or committed files.

## Local development in the JOOservices workspace

The project has no workspace path repositories in `composer.json`. Normal
installs resolve the published `jooservices/client` and `jooservices/dto`
packages. `make clean-consumer` creates a disposable consumer and uses a path
repository only for the SDK checkout under test; its runtime dependencies
still resolve exactly as they do for a public consumer.
