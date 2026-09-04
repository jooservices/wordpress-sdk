# Authentication and errors

## Authentication

WordPress application passwords are Basic auth credentials. `create()` wires
them automatically; nothing else is needed.

For other schemes (bearer, JWT, API keys, custom middleware) build a
`jooservices/client` client with `ClientBuilder` and pass it to the
`WordPressService` constructor together with a `RequestBuilder`,
`ResponseDecoder`, and `ErrorMapper`.

## Error taxonomy

| Status | Exception | Notes |
| --- | --- | --- |
| 400 | `BadRequestException` | `rest_invalid_param` becomes `ValidationException` |
| 401 | `UnauthorizedException` | invalid credentials/application password |
| 403 | `ForbiddenException` | missing capability |
| 404 | `NotFoundException` | invalid resource id |
| 422 | `ValidationException` | carries `$params` (WordPress field map) |
| 429 | `RateLimitException` | |
| 5xx | `ServerException` | also HTML responses and undecodable bodies |
| other | `WordPressApiException` | catch-all |

All of the above extend `jooservices/exceptions` `AbstractContextAwareException`
and implement `JOOExceptionInterface`. Prefer catching the typed REST subclass
(for example `UnauthorizedException`) when handling a specific HTTP failure;
use `JOOExceptionInterface` only for cross-package catch-all handling.

Stable `errorCode()` values use `{package}.{domain}.{reason}`
(`wordpress.http.unauthorized`, `wordpress.http.notfound`, …) without embedding
the numeric status in the code.

Transport failures (timeout, DNS, connection refused) propagate from
`jooservices/client` as typed client exceptions — they are not wrapped.

## Diagnostics

`WordPressApiException::toArray()` produces a structured, sanitized payload
(including `error_code`):

```php
try {
    $wordpress->posts()->get(999);
} catch (WordPressApiException $exception) {
    $payload = $exception->toArray();
    // type, message, status_code, wordpress_code, wordpress_data, response, previous, error_code
}
```

Credential-like values (`authorization`, `password`, `token`, `secret`,
`cookie`, `Basic `/`Bearer ` values, app-password-shaped strings) are
redacted recursively. Redaction is defense in depth — never log credentials
in the first place.