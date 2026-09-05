# Security Policy

## Reporting a vulnerability

Please report security issues privately to **admin@jooservices.com**. Do not
open a public issue for security problems.

We will acknowledge receipt within 2 business days and coordinate a fix and a
release. If the vulnerability is accepted, we will credit the reporter (unless
anonymity is requested).

## Scope

- This package only. For issues in `jooservices/client` or `jooservices/dto`,
  report them to the respective repository.
- Use WordPress application passwords via environment variables. Never commit
  live credentials, and never log authorization headers or application
  passwords. This SDK redacts credential-like values from exception payloads
  (`WordPressApiException::toArray()`), but redaction is defense in depth, not
  a substitute for secret hygiene.

## Supported versions

| Version | Security fixes |
| --- | --- |
| 4.x | Yes |
| 1.x | No — upgrade to 4.x |

Authenticated credentials require HTTPS by default. The
`allowInsecureHttp: true` escape hatch is only for isolated local test
environments. Release artifacts are created from tags whose commits are
verified to be reachable from protected `master`.
