# Review Findings — WordPress SDK Audit Hardening

## Review outcome

Reviewer Rounds 1–3 passed. F-1 and F-3 are fixed. F-2's output contract was
resolved with the user: preserve Quote's existing raw HTML default for the
minor release, and keep escaping available as an explicit option. The docs
warn callers to escape or sanitize untrusted quote input.

## Finding ledger

| ID | Severity | Status | Finding and disposition |
| --- | --- | --- | --- |
| F-1 | Required | Verified / closed | `AbstractBlock` accepted reserved `core/paragraph`; it now rejects `core/` names and has a regression test. |
| F-2 | Required | Resolved by approved API decision | Preserve the legacy raw HTML default for compatibility; `escapeHtml: true` opts into escaping. Parser reconstruction preserves existing Gutenberg markup, and untrusted input guidance is documented. |
| F-3 | Required | Verified / closed | Exception redaction missed encoded keys, semicolon parameters, fragments, structured/log surfaces, and changed the shared redactor; these cases are covered without changing raw `getMessage()`. |

## F-1 — Reject reserved core block names

- **Severity / status:** Required; verified and closed.
- **Evidence:** `src/Support/ContentBuilder/AbstractBlock.php` and
  `tests/Unit/Support/ContentBuilder/GenericBlockTest.php`.
- **Finding:** `AbstractBlock` accepted the reserved block name
  `core/paragraph`.
- **Disposition:** Reserved `core/` names are rejected; a regression test
  covers `core/paragraph`.
- **Verification:** Reviewer confirmed the rejection and regression coverage.

## F-2 — Preserve the Quote compatibility and escaping contract

- **Severity / status:** Required; resolved by the user's approved compatibility choice.
- **Evidence:** `src/Support/ContentBuilder/Blocks/Core/Quote.php`,
  `src/Support/ContentBuilder/Parser/BlockParser.php`,
  `tests/Unit/Support/ContentBuilder/Blocks/Core/QuoteTest.php`, and
  `tests/Unit/Support/ContentBuilder/Parser/BlockParserTest.php`.
- **Finding:** The previous review proposed escaping Quote by default, which
  would change existing markup output for callers that pass HTML.
- **Disposition:** `Quote` and `ContentBuilder::quote()` preserve raw output by
  default. Callers can set `escapeHtml: true`; the public parsed-markup factory
  escapes by default, while the parser preserves markup it just parsed.
- **Verification:** Unit tests cover both output modes and parsed markup. The
  dedicated WordPress E2E post demonstrates the legacy and opt-in modes.

## F-3 — Sanitize WordPress exception diagnostics without global mutation

- **Severity / status:** Required; verified and closed.
- **Evidence:** `src/Exceptions/WordPressApiException.php` and
  `tests/Unit/Exceptions/WordPressApiExceptionTest.php`.
- **Finding:** Redaction missed percent-encoded sensitive keys,
  semicolon-delimited parameters, URL fragments, structured context, and log
  output, and mutated the process-wide redactor.
- **Disposition:** Sensitive exception messages, URL userinfo/query/fragment,
  structured context, and log output are sanitized without changing the
  process-wide redactor. Raw `getMessage()` remains unchanged.
- **Verification:** Reviewer confirmed coverage for the reported surfaces and
  preservation of the shared redactor and raw message.

## Scorecard

Critical open: 0 · Required open: 0 · Required verified: 2 · Resolved by user decision: 1 · Optional open: 0 · Nit open: 0
