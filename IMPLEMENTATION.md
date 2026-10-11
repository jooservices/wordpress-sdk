# Implementation — WordPress SDK Audit Hardening

## Scope

This work hardens `jooservices/wordpress-sdk` only. The SDK requires
`jooservices/client ^4.5`, resolved to `4.5.0`, and its Docker WordPress E2E
target is pinned to WordPress `7.1.3`. No source changes were made to
`jooservices/client` or `wordpress-mcp`. The approved release target is the
next minor version, `4.2.0`, after implementation and verification merge to
`develop`; release metadata and tagging follow the normal release branch flow.

Approved behavior:

- Quote preserves its previous raw HTML output by default; callers can opt in
  to escaping content and citations with `escapeHtml: true`. Parsing preserves
  trusted existing Gutenberg markup.
- Button blocks reject `javascript:`, `data:`, and `vbscript:` after ASCII
  whitespace/control normalization. REST and custom-route URI schemes are not
  restricted by this change.
- Every ContentBuilder change is rendered and read back from the dedicated
  WordPress E2E review post.

## Acceptance criteria

| AC | Acceptance criterion | Status |
| --- | --- | --- |
| AC-1 | Require `jooservices/client ^4.5` and lock the resolved client to `4.5.0`. | Done |
| AC-2 | Pin the WordPress E2E image to `7.1.3`. | Done |
| AC-3 | Validate generic block names, reject reserved `core/` names, and safely JSON-encode attributes so they cannot terminate a Gutenberg comment. | Done |
| AC-4 | Preserve Quote's existing raw HTML output by default, support opt-in escaping, and preserve trusted parsed inline Gutenberg markup through parser behavior. | Done |
| AC-5 | Reject the approved executable URI schemes in Button links after ASCII whitespace/control normalization without restricting REST/custom-route URI schemes. | Done |
| AC-6 | Prevent a ReadMore label from terminating its Gutenberg HTML comment. | Done |
| AC-7 | Normalize accepted URL scheme casing and omit supplied URL/input from invalid-URL errors. | Done |
| AC-8 | Redact sensitive data in exception messages, URLs, structured context, and logs without mutating the shared redactor or changing raw `getMessage()`. | Done |
| AC-9 | Add regression/unit and full Docker WordPress E2E coverage; update SDK documentation and commands. | Done |
| AC-10 | Retain a dedicated ContentBuilder E2E review post and verify its rendered/read-back content. | Done |

## Completed tasks

| Task | AC traceability | Status | Implementation and test evidence |
| --- | --- | --- | --- |
| 1. Pin the client dependency and WordPress E2E runtime. | AC-1, AC-2 | Done | `composer.json`, `composer.lock`, `docker-compose.integration.yml`; consumer and integration verification in [qa.md](qa.md). |
| 2. Preserve Quote output by default and add opt-in escaping. | AC-4 | Done | `src/Support/ContentBuilder/Blocks/Core/Quote.php`; `tests/Unit/Support/ContentBuilder/Blocks/Core/QuoteTest.php`. |
| 3. Preserve trusted parsed Quote markup without opening a public escaping bypass. | AC-4 | Done | `src/Support/ContentBuilder/Parser/BlockParser.php`, `src/Support/ContentBuilder/ContentBuilder.php`; `tests/Unit/Support/ContentBuilder/Parser/BlockParserTest.php`, `tests/Unit/Support/ContentBuilder/ContentBuilderTest.php`, and `tests/Unit/Support/ContentBuilder/Blocks/Core/QuoteTest.php`. |
| 4. Reject executable schemes in Button block links. | AC-5 | Done | `src/Support/ContentBuilder/Blocks/Core/Button.php`; `tests/Unit/Support/ContentBuilder/Blocks/Core/ButtonTest.php`. |
| 5. Keep ReadMore labels inside their Gutenberg comment. | AC-6 | Done | `src/Support/ContentBuilder/Blocks/Core/ReadMore.php`; `tests/Unit/Support/ContentBuilder/Blocks/Core/ReadMoreTest.php`. |
| 6. Validate generic block names and safely encode attributes. | AC-3 | Done | `src/Support/ContentBuilder/AbstractBlock.php`; `tests/Unit/Support/ContentBuilder/GenericBlockTest.php`. |
| 7. Normalize Config URL schemes and sanitize invalid-URL errors. | AC-7 | Done | `src/Config.php`; `tests/Unit/ConfigTest.php`. |
| 8. Redact WordPress API exception diagnostics safely. | AC-8 | Done | `src/Exceptions/WordPressApiException.php`; `tests/Unit/Exceptions/WordPressApiExceptionTest.php`. |
| 9. Complete regressions, Docker E2E, and SDK documentation/command updates; retain and inspect the review sample. | AC-9, AC-10 | Done | `tests/Integration/WordPressIntegrationTest.php`, `Makefile`, `tools/integration`, `docker-compose.integration-review.yml`, `README.md`, `CHANGELOG.md`, `docs/01-getting-started/installation.md`, `docs/02-user-guide/media-and-content-builder.md`, and `docs/03-development/setup.md`. See [qa.md](qa.md) for commands and results. |

**Review:** F-1 and F-3 verified; F-2 resolved by the approved compatibility decision; see [findings.md](findings.md).

**QA:** Local gates passed; remote-gate limitations and independent review-sample evidence are recorded in [qa.md](qa.md).

## Decision log

| Decision | Approved choice | Rationale |
| --- | --- | --- |
| Repository scope | `wordpress-sdk` only; no `client` or `wordpress-mcp` edits. | Keep this delivery within the approved SDK audit boundary. |
| Dependency and E2E runtime | Require `jooservices/client ^4.5` resolved to `4.5.0`; use WordPress `7.1.3` for Docker E2E. | These are the approved dependency and test-runtime targets. |
| ContentBuilder output | Preserve Quote's legacy raw HTML default, allow opt-in escaping, preserve trusted parsed Gutenberg markup in parser flow, and reject the approved executable schemes for Button blocks. | Keep existing Quote output compatible while leaving REST/custom-route schemes unrestricted. |
| Review sample | Keep the dedicated ContentBuilder WordPress post and review stack available for user inspection. | Every ContentBuilder change must be rendered and read back in a dedicated WordPress E2E sample. |
| SDK release | Prepare the next minor release, `4.2.0`, after the implementation PR merges to `develop`. | Keep release metadata isolated on `release/4.2.0` under the repository release process. |
