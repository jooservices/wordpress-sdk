# QA Evidence — WordPress SDK 4.2.0 Candidate

- **Date:** 2026-10-11
- **Repository scope:** `wordpress-sdk` only; no changes to `client` or
  `wordpress-mcp`.
- **Release target:** `4.2.0`, pending the approved PR and release flow.
- **Runtime:** SDK test image PHP 8.5.11, Composer 2.10.3, PHPUnit 13.4.1,
  PCOV 1.0.12. WordPress E2E uses WordPress 7.1.3, WP-CLI 2.12.0, and
  MariaDB 12.3.3.

## Behavior reviewed

- `Quote` retains the legacy raw HTML default. `escapeHtml: true` opts into
  escaping its content and citation. Callers should escape or sanitize
  untrusted quote input.
- `Button` rejects `javascript:`, `data:`, and `vbscript:` links after
  whitespace/control normalization. REST and custom-route schemes are not
  restricted.
- Generic Gutenberg block names and attributes are validated/encoded;
  `ReadMore` labels cannot escape their Gutenberg comment.
- WordPress exception diagnostics redact credentials across messages,
  structured context, URLs, and logs without changing the shared redactor.

## Verification

| Check | Result |
| --- | --- |
| `composer ci` in PHP 8.5.11 container | PASS: all lint stages; 341 tests / 1,125 assertions; 93.89% coverage (1,598/1,702 statements, above the 90% gate); Composer audit reported no advisories. |
| `composer validate --strict` | PASS. |
| `composer outdated --all --locked --format=json` | PASS: all 88 locked runtime, dev, and transitive packages are current; no newer versions reported. |
| Disposable WordPress E2E via `make integration` | PASS: 4 tests / 84 assertions. Stack and volumes were cleaned after the run. |
| Retained review sample via `make integration-review-sample` | PASS: 4 tests / 83 assertions. A dedicated published post was read back through the SDK and verified with WP-CLI. |
| Sample page HTTP check | PASS: HTTP 200 at the review URL below. |
| `make clean-consumer` | PASS: consumer installation resolved `jooservices/client v4.5.0` and the SDK smoke check passed. |
| `make build` | PASS: built the PHP 8.5.11 / Composer 2.10.3 tooling image. The integration runner now rebuilds its PHP service before running tests. |
| Actionlint 1.7.12 | PASS: all GitHub workflows parsed. |
| Gitleaks 8.30.1 | PASS: 54 commits scanned, no leaks found. |
| Semgrep 1.179.0 (`p/php`) | PASS: 23 rules, 136 PHP files, 0 findings. |
| `sh -n tools/integration` and `git diff --check` | PASS. |

## Dedicated WordPress review post

The retained review stack uses a separate project name and port, preserving
the pre-existing stack on port 8080. The new WordPress and MariaDB containers
are healthy on port 8081.

- Post ID: `5`
- Status: `publish`
- Title: `ContentBuilder E2E Review Sample sdk-content-builder-review-57a0efbd046b312e9360dba274265091`
- URL: [review the ContentBuilder sample](http://127.0.0.1:8081/2026/10/11/sdk-content-builder-review-57a0efbd046b312e9360dba274265091/)
- The page demonstrates raw Quote markup by default, opt-in escaped Quote
  content/citation, a safe HTTPS Button, a ReadMore marker, and a generic block
  with safely serialized attributes. The integration test reads the stored
  post back and checks the block values and markup behavior.
- WordPress normalizes Gutenberg whitespace and JSON escape spelling when
  storing posts; the E2E assertions check persisted block meaning and content,
  not byte-identical markup.
- The existing review stack at `http://127.0.0.1:8080` remains healthy.

Keep the port-8081 stack running for review. After review, remove it with:

```bash
WORDPRESS_REVIEW_PROJECT=wordpress-sdk-integration-review-420 make integration-review-cleanup
```

## Remaining release gates

The local checks above are green. GitHub-hosted CI, OSV Scanner, Dependency
Review, Codecov, and the configured Sonar job must pass on the implementation
PR before merge. Release metadata, the `release/4.2.0` PR, tag, and back-merge
remain pending the JOOservices release sequence.
