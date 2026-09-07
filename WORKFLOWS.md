# CI/CD workflows

This document describes the workflows currently defined in
`.github/workflows/`. All jobs run on GitHub-hosted `ubuntu-latest` runners.
PHP, Composer, WordPress, WP-CLI, scanners, and workflow validation run in
containers via the repository Docker Compose wrapper
(`tools/ci/docker-compose`).

## Pull request checks

- `Validate`
- `Lint (Pint)`, `Lint (PHPCS)`, `Lint (PHPStan)`, `Lint (PHPMD)`, and `Lint (PHP-CS-Fixer)`
- `Unit coverage` with the 90% aggregate and zero-covered symbol gates
- `Coverage upload` (Codecov + Sonar)
- `Clean consumer`
- `WordPress E2E`
- `Security (Dependencies)`, including Composer audit, OSV Scanner, and dependency review
- `Security (Secrets)` with Gitleaks
- `Security (SAST)` with Semgrep
- Commitlint, semantic PR title, CodeQL, PR labeler, and workflow audit when applicable

`ci-post-merge.yml` repeats the package and integration gates on `master` and
`develop`, then uploads the package coverage report to Codecov so each branch
head can be used as a comparison base. Dependabot covers Composer and GitHub
Actions weekly.

## Other workflows

| Workflow | Trigger | Flow / result |
| --- | --- | --- |
| `codeql.yml` | Push/PR on `master` or `develop`; weekly | CodeQL for GitHub Actions → publish security results |
| `commitlint.yml` | PR opened, edited, synchronized, reopened | Validate every PR commit against `.github/commitlint.config.mjs` |
| `semantic-pr.yml` | PR opened, edited, synchronized, reopened | Validate PR title; skipped for Dependabot |
| `pr-labeler.yml` | PR opened, synchronized, reopened | Apply labels from `.github/labeler.yml` |
| `link-check.yml` | Monday 04:00 UTC; manual | Lychee Markdown link check |
| `scorecard.yml` | Push to `master`; Monday 00:00 UTC; manual | OpenSSF Scorecard → SARIF upload |
| `stale.yml` | Daily 01:00 UTC; manual | Stale after 60 days; close 14 days later |
| `workflow-audit.yml` | Push/PR on `master` or `develop` when `.github/**` changes; Monday 03:00 UTC; manual | Actionlint + Zizmor |

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

## Branch protection

Both `master` and `develop` require pull requests with these status checks:
`Validate`, `Lint (Pint)`, `Lint (PHPCS)`, `Lint (PHPStan)`, `Lint (PHPMD)`,
`Lint (PHP-CS-Fixer)`, the three `Security (…)` legs,
`Unit coverage`, `Coverage upload`, `Clean consumer`, `WordPress E2E`, `Validate commit messages`,
`Validate PR title`, and `Analyze GitHub Actions`. Strict mode requires the
branch to be up to date. Force pushes and deletions are denied. Merged head
branches are deleted automatically.

## Notes

- All jobs use GitHub-hosted `ubuntu-latest`. There is no self-hosted runner pool.
- All declared workflows use dedicated repository configuration; none use
  `jooservices/workflows`.
- Secret scanning has two layers: GitHub Secret Scanning and Push Protection
  at GitHub, plus Gitleaks OSS in the pull-request security matrix.
- Codecov and Sonar run in the `Coverage upload` leaf job.
