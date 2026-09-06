# Contributing

Thanks for contributing to `jooservices/wordpress-sdk`.

## Workspace rules

This repository belongs to the JOOservices workspace. Follow the workspace
root `AGENTS.md` — git identity, GitHub account, branch model, commit
conventions, Docker-only PHP tooling, and the PHP Language Standard apply
unchanged.

## Scope boundary

This package is a WordPress REST API SDK and nothing more:

- **In scope:** native REST client surface, typed DTOs, query DTOs, auth,
  pagination, discovery/schema helpers, error mapping, generic payload
  helpers (PostBuilder, ContentBuilder).
- **Out of scope:** editorial content templates, publishing workflows, and
  plugin/theme-specific logic. Such code belongs in separate packages that
  depend on this SDK, never inside it.

## Development

All PHP tooling runs through Docker:

```bash
make install   # build image + composer install
make lint      # pint --test + phpstan (level max)
make test      # phpunit Unit suite
make clean-consumer
make integration
make ci        # lint + coverage + audit
make hooks-install # opt-in; runs project hooks through the Docker PHP wrapper
```

## Quality gates

- Pint `per` preset (PER-CS 3.0) and PHPStan level `max` on `src/` and `tests/`.
- Every change ships with tests that exercise the real request path through
  `jooservices/client` fakes — no mock-only service tests.
- Coverage gate: aggregate statement coverage >= 90%; no coverable file,
  class, or method at 0% without a documented reason.

## Commits

Conventional Commits with an uppercase imperative subject:

```text
feat: Add strict request-query allowlists
fix: Correct after-commit event dispatch
```

Do not add AI attribution trailers (`Co-authored-by`, `Generated with …`).

All changes enter `develop` through a pull request from a short-lived branch.
Releases use `release/<version>` into `master`, an annotated tag from the
green `master` commit, and a `master` → `develop` merge-back pull request.
See [WORKFLOWS.md](./WORKFLOWS.md).
