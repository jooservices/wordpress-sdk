# CI/CD workflows

All repository jobs use `[self-hosted, Linux, X64]`. PHP, Composer, WordPress,
WP-CLI, scanners, and workflow validation run in containers. The Docker Compose
wrapper exports the runner UID/GID so bind-mounted `vendor`, coverage, build,
and cache files are not root-owned.

## Pull request checks

- `Validate`
- `Lint (Pint)` and `Lint (PHPStan)`
- `Unit coverage` with the 90% aggregate and zero-covered symbol gates
- `Clean consumer`
- `WordPress E2E`
- `Security (Dependencies)`, including Composer audit and dependency review
- `Security (Secrets)` with Gitleaks
- `Security (SAST)` with Semgrep
- Commitlint, semantic PR title, CodeQL, and workflow audit when applicable

`ci-post-merge.yml` repeats the package and integration gates on `master` and
`develop`. Dependabot covers Composer and GitHub Actions weekly.

## Branch and release flow

Normal changes branch from `develop` and merge back through a green PR.
`release/<version>` branches contain release metadata only and target
`master`. After the green release PR merges, an annotated tag is created from
that `master` commit. The tag workflow re-runs the release gates, creates an
SBOM, and publishes a GitHub Release through a pinned action. It does not use a
host `gh` binary or `sudo`. Finally, `master` is merged back to `develop`
through another green PR.

See [release-and-rollback.md](docs/04-maintenance/release-and-rollback.md) for
the exact operator procedure.
