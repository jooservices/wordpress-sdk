# Development

All PHP tooling runs through Docker (`php:8.5-cli-bookworm`). The Makefile
wraps the composer scripts.

## Commands

```bash
make build       # build the Docker image (jooservices/wordpress-sdk:php85)
make install     # composer install inside the container
make shell       # interactive container shell
make lint        # pint + phpcs + phpstan (level max) + phpmd + php-cs-fixer
make lint-fix    # pint + php-cs-fixer
make test        # phpunit Unit suite
make test-coverage
make audit       # composer audit
make clean-consumer
make integration # disposable WordPress 7.1 + MariaDB + WP-CLI
make hooks-install # optional local hooks; never automatic on install
make ci          # lint + test:coverage (coverage gate) + audit
```

Individual tool wrappers (IDE format-on-save):

```bash
tools/pint
tools/ci/docker-compose run --rm --no-deps php vendor/bin/phpstan analyse --no-progress
```

## Testing strategy

Unit tests exercise the real request path — `WordPressService` → `ClientFactory`
→ `jooservices/client` HTTP fakes (`ClientBuilder::fake()` +
`HttpFakeRegistry`) → services → decoder → DTOs. Requests are recorded and
asserted (method, URI, query, headers, JSON/multipart bodies); responses are
fed from `TestResponse` fixtures. There are no SDK-internal mocks.

Fake patterns match by path with `fnmatch` (query matching is order-independent).

## Quality gates

- Pint `per` preset (PER-CS 3.0).
- PHPStan level `max` on `src/` and `tests/` (with `phpstan-phpunit`).
- PHPUnit: random order, `failOnWarning`, `failOnRisky`,
  `failOnEmptyTestSuite`, strict output.
- Coverage gate (`tools/test-coverage-gate.php`): aggregate >= 90%; any
  coverable file, class, or method at 0% fails unless documented in the
  gate's `EXCLUSIONS` (currently none).
- `composer audit` on every CI run.
- Clean-consumer install against published `client`/`dto` releases.
- Live WordPress 7.1 route discovery and authenticated WP-CLI E2E.

## Releasing

1. Branch `release/<version>` from latest `develop`.
2. Update `CHANGELOG.md` (Keep a Changelog, semantic versioning).
3. Run `make validate`, `make ci`, `make clean-consumer`, and
   `make integration`.
4. PR `release/<version>` into `master`; tag from `master` after merge.
5. Merge `master` back into `develop`.

The complete operator checklist and rollback procedure are in
[release-and-rollback.md](../04-maintenance/release-and-rollback.md).
