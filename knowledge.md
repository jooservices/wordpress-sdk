# JOOservices WordPress SDK v4.0.0 Rebuild — Knowledge Base

> [!NOTE]
> Shared context for the `jooservices/wordpress-sdk` ground-up rebuild. Everything below was
> learned from the archived v1.x codebase (`archives/JOOservices.2/wordpress-sdk`, v1.2.0+),
> its consumers (`wordpress-content-templates`, `wordpress-management`, `laravel-wordpress`),
> and the current workspace conventions (`client` v4.1.0, `dto` v3.1.0, PHP Language Standard).
>
> This document is knowledge only. Implementation decisions are recorded here as
> **recommendations** and must be locked before code is written.

---

## 1. Workspace Context

### 1.1 Repository layout

```
/Users/vietvu/Sites/JOOservices/
├── AGENTS.md          # Canonical workspace policy (all agents must follow)
├── client/            # jooservices/client — v4.1.0 (ground-up rebuild, PSR-18 client)
├── dto/               # jooservices/dto — v3.1.0 (ground-up rebuild, DTO/Data library)
├── wordpress-sdk/     # THIS PROJECT — jooservices/wordpress-sdk v4.0.0 rebuild
├── flickr/, useragent/, go-flickr/, …  # other JOOservices packages
└── docs/              # Workspace-wide documentation (PHP Language Standard etc.)
```

### 1.2 Key workspace policies (from root AGENTS.md)

| Policy | Rule |
| --- | --- |
| Git identity | `Viet Vu <jooservices@gmail.com>` — author **and** committer, no exceptions |
| GitHub account | `soulevilx` only |
| Branch model | `master` (production) + `develop` (integration) — no `main` |
| Commits | Conventional Commits, uppercase imperative subject |
| PRs | Required for all changes into `master`/`develop`; CI green |
| Quality | Lint + static analysis + tests before commit; never `--no-verify` |
| PHP standard | PSR-1/PSR-4, PER-CS 3.0 via Pint `per` preset, PHP `^8.5`, `declare(strict_types=1)`, `final` + `readonly` for DTOs/VOs, canonical casts |
| PSR mandate | Type against PSR interfaces where a PSR exists (PSR-7, PSR-18, PSR-3, PSR-11, …) |
| Docker tooling | **All PHP tooling runs through Docker** (`php:8.5-cli-bookworm`), pattern below |

### 1.3 Workspace Docker pattern (learned from client/dto/useragent/flickr)

Every PHP package ships the same trio; the rebuild must copy it:

- **`Dockerfile`** — `FROM php:8.5-cli-bookworm`; apt: `git unzip libzip-dev $PHPIZE_DEPS`; `docker-php-ext-install zip`; `pecl install pcov && docker-php-ext-enable pcov`; `COPY --from=composer:2 /usr/bin/composer /usr/bin/composer`; `ENV COMPOSER_ALLOW_SUPERUSER=1 COMPOSER_HOME=/tmp/composer`; `WORKDIR /app`.
- **`docker-compose.yml`** — single service `php`: `build: .`, `image: jooservices/<package>:php85`, `working_dir: /app`, volume `.:/app`, `user: "${DOCKER_UID:-0}:${DOCKER_GID:-0}"`, `tty: true`.
- **`Makefile`** — `DOCKER_COMPOSE ?= docker compose`, `PHP = $(DOCKER_COMPOSE) run --rm php`; targets `build install shell validate lint test test-coverage audit ci` mapping to `composer` scripts.
- Optional **`tools/<tool>`** wrappers (e.g. `tools/pint`) for IDE format-on-save:
  `exec docker compose run --rm --no-deps php vendor/bin/pint "$@"`.

### 1.4 Dependency landscape for v4

| Package | Current workspace version | What it provides |
| --- | --- | --- |
| `jooservices/client` | v4.1.0 | PSR-18 HTTP client (`HttpClient implements Psr\Http\Client\ClientInterface`), `ClientBuilder` (fluent: `withBaseUri`, `withTimeout`, `withConnectTimeout`, `withBasicAuth`, `withRetry(RetryConfig)`, `withLogger`, `withHeader`, middleware pipeline), `Response` wrapper, typed exceptions (`TimeoutException`, `NetworkConnectionException`, `RequestException`, `RateLimitExceededException`, …), testing fakes (`ClientBuilder::fake()`, `HttpFakeRegistry`, `recorded()`) |
| `jooservices/dto` | v3.1.0 | `Dto`/`Data` base classes: `from()`/`fromArray()`/`fromJson()`/`fromObject()`/`collection()` static hydration, `toArray()`, `with()`, `merge()`, validation attributes, casting engine, `ValidationException` |

Both are required as runtime deps (the v1 SDK already depended on them: `jooservices/client ^0.5.0`, `jooservices/dto ^1.0` — v4 must move to `^4.2` / `^3.2`). They resolve from GitHub/packagist in the workspace; no `repositories` section in sibling composer.json files.

Client v4 facts verified in code (v4.2.0):
- `HttpClient::sendRequest(RequestInterface): ResponseInterface` (PSR-18) and `send(RequestInterface, array|RequestOptions)`.
- Relative URIs are resolved against the configured base URI (`applyBaseUri`) — SDK service URIs stay relative (`wp/v2/posts`).
- HTTP errors return responses, never throw (`http_errors => false` in GuzzleSender) — the SDK's `ErrorMapper` pattern works unchanged.
- **Multipart supported natively since v4.2.0**: client `RequestBuilder::withMultipart(array $parts)` + `MultipartPart(name, contents: string|resource|StreamInterface, filename, contentType, headers)` + `PreparedRequest` (`toPsr()`, `options()`); `withJson()` swaps Content-Type back when a multipart body was replaced. Media upload flow: `$client->requestBuilder()->post($path)->withMultipart([new MultipartPart('file', $stream, filename: …)])->build()->toPsr()` → `sendRequest()`. No SDK-side multipart builder needed.
- **Base URI trailing slash fixed in v4.2.0**: `HttpClient::applyBaseUri` normalizes `baseUri` via `BaseUri::normalize()` before RFC-3986 resolution — `https://site/wp-json` (no slash) no longer drops the last path segment.
- `withBasicAuth()` exists natively — WordPress application passwords are Basic auth; the SDK does **not** need its own authenticator/middleware.
- `ClientBuilder::fake()` + `HttpFakeRegistry::respond($method, $pattern, $sequence)` + `recorded()` — this is the v4 unit-test strategy (replaces the v1 `NullHttpClient` hack).

dto facts verified in code (v3.2.0):
- `from()`/`fromArray()`/`fromJson()`/`collection()` hydrate DTOs from arrays; unknown keys (`_links`, `_embedded`, …) are ignored; `false` casts to `''` for `string`; `'42'` casts to `42` for `int`; `'1'` casts to `true` for `bool`; nested DTOs hydrate from arrays; `null` → non-nullable still throws `CastException` (SDK rule: nullable types).
- **Array casting fixed in v3.2.0**: plain `array` (no docblock) passes through; `list<int>` casts each item; `array<string, mixed>` / `array<string, string>` work with correct placement.
- **`@param` docblocks on the constructor are now read** (v3.2.0) — no per-property `@var` noise on 10–20-param DTOs.
- `DataCollection::toArray()` exists (v3.2.0); SDK still ships its own `PaginatedCollection` for WP headers.

---

## 2. Archived v1.x Architecture (What We Learned)

Archive: `archives/JOOservices.2/wordpress-sdk` (v1.2.0 + unreleased work, PHP `>=8.5`).
Namespace: `JOOservices\WordPress\Sdk\` → `src/` (PSR-4).

### 2.1 Directory/namespace map

| Directory | Responsibility |
| --- | --- |
| `src/WordPressService.php` | Facade entry point (`WordPressService::create()`), 33 lazy service getters |
| `src/ContainerFactory.php` | PHP-DI wiring (optional advanced path) |
| `src/Configs/` | `AuthConfig`, `HttpConfig`, `SdkConfig` (final readonly value objects) |
| `src/Contracts/` | Interfaces; sub-namespaces `Readable/`, `Writable/`, `Special/` |
| `src/Http/` | `AbstractService`, `ClientFactory`, `ErrorMapper`, `RequestBuilder`, `ResponseDecoder`, `ResponseDecoderLogger`, `Middleware/AuthenticationMiddleware` |
| `src/Auth/` | `BasicAuthenticator`, `AppPasswordAuthenticator` (byte-identical) |
| `src/Endpoints/` | `CoreEndpoint`, `TaxonomyEndpoint` (backed string enums) |
| `src/Exceptions/` | `WordPressApiException` + 7 subclasses |
| `src/Pagination/` | `PaginatedCollection` |
| `src/Services/` | 33 services: typed CRUD + raw admin/editor groups |
| `src/Data/` | DTOs + `Data/Query/` query DTOs |
| `src/Support/` | `RestPath`, `PostBuilder`, `ContentBuilder/**` (blocks + parser) |

### 2.2 Request lifecycle (verified in code)

```
App → WordPressService::create() → posts() [lazy] → get(123)
  → AbstractService::getItem() → RequestBuilder (immutable) → PSR-7
  → jooservices/client HttpClient (auth middleware injects Authorization: Basic …)
  → Guzzle → WP API → ResponseWrapper → PSR-7
  → (≥400 → ErrorMapper → typed exception)
  → ResponseDecoder (Symfony Serializer) → Post DTO / PaginatedCollection
```

### 2.3 Contracts (v1 inventory, with dead-code findings)

| Interface | Methods | Implemented by | Status |
| --- | --- | --- | --- |
| `AuthenticatorInterface` | `authenticate(RequestInterface): RequestInterface` | `BasicAuthenticator`, `AppPasswordAuthenticator` | live (but only Basic wired) |
| `QueryParametersInterface` | `toQuery(): array` | `AbstractListQuery` + children | live |
| `ResponseDecoderInterface` | `decodeItem`, `decodeList`, `decodeArray`, `deserialize` | `ResponseDecoder` | live |
| `ServiceInterface` | (empty marker) | **nobody** | **dead** |
| `GettableInterface` | `get(int $id, array\|QueryParametersInterface\|null $params = null): object` | Posts, Pages, Comments, Users, Media, AbstractTermService | live |
| `ListableInterface` | `list(...): PaginatedCollection` | same + SearchService | live |
| `CreatableInterface` | `create(array $payload): object` | typed CRUD services | live |
| `UpdatableInterface` | `update(int $id, array $payload): object` | typed CRUD services | live |
| `DeletableInterface` | `delete(int $id, bool $force = false): object` | typed CRUD services | live |
| `AuthenticatedUserInterface` | `me(): object` | **nobody** (UsersService::me() exists with different signature) | **dead** |
| `RevisionableInterface` | `revisions(int $id): array` | **nobody** | **dead** |
| `HydratorInterface` | `hydrate`, `hydrateList` | **nobody** (Symfony Serializer does the job) | **dead** |

**v4 rule: every shipped contract must have at least one implementer.** Dead contracts are deleted.

### 2.4 HTTP layer (v1 details worth preserving)

- **`RequestBuilder`** — immutable, `final`, promoted readonly props `(string $method = 'GET', string $uri = '', array $headers = [], ?StreamInterface $body = null, string $version = '1.1')`; withers `withMethod/withUri/withHeader/withBody/withJson` (each returns `self`); `build(): RequestInterface`. ⚠️ **No query support** — query strings were concatenated in `AbstractService` (`?`/`&` + `http_build_query`). Fix in v4: `withQuery(array $query): self` in the builder.
- **`AbstractService`** — constructor `(HttpClientInterface $client, RequestBuilder $requestBuilder, ResponseDecoderInterface $decoder, ErrorMapper $errorMapper)`; protected primitives:
  - `request($method, $uri, $options): ResponseInterface` — single dispatch chokepoint; maps status ≥400 via `ErrorMapper`.
  - `getItem($uri, $dtoClass, $options): object` — GET + decodeItem; wraps ServerException with `[URI: ...]` context.
  - `getList(...): PaginatedCollection`, `createItem(...)` (POST), `updateItem(...)` (**POST**, never PUT/PATCH), `deleteItem(...)` (DELETE + decodeItem), `requestArray(...)` (raw json_decode).
  - `normalizeQueryParameters(array|QueryParametersInterface|null): array` — `toQuery()` for DTOs.
  - `normalizeReadOptions(array): array` — ⚠️ magic heuristic: bare param arrays become `['query' => $params]`. Fix in v4: explicit `'query'` key only.
  - Pagination primitives: `collectAll()`, `cursorItems(): \Generator` (page loop over `$collection->totalPages`), `eachItem(callable)` (early exit on `false`).
  - ⚠️ `request()` wrapped **all** `\Throwable` into generic `RuntimeException` — losing client exception taxonomy (consumers had to string-match error messages). Fix in v4: let `jooservices/client` typed exceptions propagate.
- **`ResponseDecoder`** — `final readonly`, wraps `SerializerInterface&DenormalizerInterface` (Symfony). HTML-body guard (starts with `<!DOCTYPE html>`/`<html`) → `ServerException`. `decodeList` reads `X-WP-Total` / `X-WP-TotalPages` headers. Handles both list-shaped (`array_is_list`) and assoc-shaped payloads (types/statuses keyed by slug). ⚠️ Global static logger (`ResponseDecoderLogger` singleton writing to a temp file by default). Fix in v4: injectable `?Psr\Log\LoggerInterface`, and hydrate with `jooservices/dto` `from()` instead of Symfony Serializer (kills 4 Symfony deps).
- **`ErrorMapper`** — `map(ResponseInterface): WordPressApiException`; `match` on status code: 400 (but `rest_invalid_param` → `ValidationException` with per-field params from `data.params`), 401, 403, 404, 422, 429, 5xx, catch-all. ⚠️ Contained a dead empty HTML-detection block. Fix in v4: remove.
- **`ClientFactory`** — `ClientBuilder::create()->withBaseUri()->withTimeout()->withConnectTimeout()->withOption('retries', …)`; ⚠️ `maxRetries` config was dead (option never consumed). Fix in v4: `withRetry(RetryConfig)` (client-native, defaults `maxAttempts: 3`, retries only GET/HEAD/PUT/DELETE/OPTIONS — safe for WP).

### 2.5 Config design (v1 → v4 decision)

v1 had three classes: `AuthConfig` (username, password), `HttpConfig` (timeout=30, connectTimeout=10, maxRetries=3), `SdkConfig` (baseUrl normalized: valid URL → rtrim `/` → append `/wp-json` → trailing `/`).

**v4 recommendation: one `Config` value object** (`final readonly`): `baseUrl` (same normalization: site root → `<root>/wp-json`), `username`, `password`, `timeout`, `connectTimeout`, `?RetryConfig $retry` (default `new RetryConfig()`), `?LoggerInterface $logger`. Validates URL + positive timeouts. Kills the AuthConfig/HttpConfig/SdkConfig ceremony and the dead `maxRetries`.

### 2.6 Auth design (v1 → v4 decision)

v1: `AuthenticatorInterface` + `BasicAuthenticator` + `AppPasswordAuthenticator` (byte-identical) + `AuthenticationMiddleware` wired into the client pipeline. `AppPasswordAuthenticator` was never wired anywhere.

**v4 recommendation: delete the SDK auth layer entirely.** `ClientBuilder::withBasicAuth($username, $password)` is exactly what WordPress application passwords need, works on every transport, and the client already exposes bearer/api-key/custom middleware for anything exotic. One less abstraction, zero dead code. (Consumer pain point "bearer/JWT unsupported" is a client concern, not an SDK one — documented in §5.)

### 2.7 Endpoints (v1 → v4 decision)

v1: two enums with inconsistent path forms — `CoreEndpoint` values without leading slash, `TaxonomyEndpoint` values **with** leading slash; `CoreEndpoint::INDEX = 'wp-json'` unused; `CoreEndpoint::POSTS_BY_STATUS = 'wp/v2/posts?status=%s'` embedded a query string in an enum value (sprintf hack in `withValues()`).

**v4 recommendation: one `Endpoint` enum** (string-backed, all values relative without leading slash, no query strings, no unused values), methods `path()`, `withId(int|string $id)`, `withKey(string $key)`, `withValues(array $values)`. Revisions/application-password sub-paths built by concatenation in the service (single place, documented).

### 2.8 Exceptions (v1 → v4)

`WordPressApiException extends \RuntimeException` with `public readonly ?array $data` (raw WP payload) and `toArray()` **with recursive sensitive-value redaction** (`authorization`, `password`, `application_password`, `app_password`, `token`, `secret`, `cookie`, `set-cookie` keys; `Basic `/`Bearer ` prefixes; 4-4-4 app-password regex). Subclasses: `BadRequestException` (400), `UnauthorizedException` (401), `ForbiddenException` (403), `NotFoundException` (404), `ValidationException` (422, carried Symfony `ConstraintViolationListInterface`), `RateLimitException` (429), `ServerException` (500/502/503/504).

**v4 recommendation:** keep the hierarchy + redaction exactly; replace the Symfony `ConstraintViolationList` in `ValidationException` with a plain `array $params` (WP `data.params` map) — drops `symfony/validator`; enrich `toArray()` for validation errors (v1 passed `data: null` and lost the payload).

### 2.9 PaginatedCollection (v1 → v4)

v1: `IteratorAggregate<int, TDto>` + `Countable`; `(array $items, public readonly int $total, public readonly int $totalPages)`; `all(): array`. No Link-header parsing, no map/next/prev helpers.

**v4 recommendation:** keep minimal (KISS/YAGNI); cursor pagination uses `totalPages`. Link-header traversal is roadmap, not v4.

### 2.10 Data layer (DTOs)

All DTOs extended `JOOservices\Dto\Core\Dto` (immutable, readonly properties) but hydration was done by **Symfony Serializer**, leaving the DTO package's engine unused — a layer violation.

DTO inventory with field sets (v1 → v4 notes):

| DTO | Fields | v4 notes |
| --- | --- | --- |
| `Post` | id, date, date_gmt, guid, modified, modified_gmt, slug, status, type, link, title, content, excerpt (4 × `RenderedContent`), author, featured_media, comment_status, ping_status, sticky, template, format, meta, categories (int[]), tags (int[]) | make all fields optional-with-defaults (v1 required 23 positional args — consumer pain); keep nesting |
| `Page extends Post` | same | keep inheritance (DRY) — safe once Post ctor is defaulted; drop the re-declared constructor |
| `Media` | id, date, guid, slug, status, type, link, title, caption, description (3 × `RenderedContent`), alt_text, media_type, mime_type, media_details (array), author, source_url | defaulted ctor |
| `Comment` | id, post, parent, author, author_name, author_url, date, date_gmt, content (`RenderedContent`), link, status, type, author_avatar_urls, meta | — |
| `User` | id, name, url, description, link, slug, avatar_urls, meta (+ `username` **derived from slug — undocumented hack**) | fix: `?string $username = null`, never derived |
| `Term` | id, count, description, link, name, slug, taxonomy, parent, meta | — |
| `Taxonomy` | slug, name, types, rest_base, hierarchical, description, rest_namespace, labels | — |
| `PostType` | slug, name, rest_base, hierarchical, viewable, description, rest_namespace, labels, supports, taxonomies | — |
| `Status` | name, public, protected, private, queryable, show_in_list, date_floating | — |
| `Settings` | values (array<string,mixed>) + `get(string $key, mixed $default = null)` | only DTO with behavior |
| `SearchResult` | id, title, url, type, subtype | — |
| `RenderedContent` | rendered, protected, raw | the nested WP `{raw,rendered,protected}` object |
| `ApplicationPassword` | uuid, app_id, name, created, last_used, last_ip, password (present only on create) | — |

v1 had **two inconsistent construction styles** (explicit props + body assignment vs promoted constructor). v4: promoted constructors everywhere, all params defaulted where WP may omit them.

### 2.11 Query DTOs

`AbstractListQuery extends Dto implements QueryParametersInterface`: page, perPage, search, context, orderby, order, include, exclude, fields (→`_fields`), embed (→`_embed` when true); `toQuery()` = array_filter removing null/`[]`.

Children (each **re-implemented** `toQuery()` + filter in v1 — DRY smell): `ListPostsQuery` (+author/authorExclude/categories/tags/status/sticky), `ListPagesQuery` (+parent/parentExclude/status), `ListCommentsQuery` (+post/parent/status/type), `ListMediaQuery` (+parent/mediaType/mimeType), `ListTermsQuery` (+hideEmpty/parent/post/slug), `ListUsersQuery` (+roles/capabilities/hasPublishedPosts), `SearchQuery` (+type/subtype).

**v4 recommendation:** single `toQuery()` in the parent with an `extraParams(): array` hook; subclasses promote their own params and override the hook. camelCase param → snake_case WP key mapping stays in one place.

### 2.12 Services layer

Every service extended `AbstractService` with the same 4-dependency constructor.

**Typed (DTO-returning) services:**

| Service | DTO | Surface |
| --- | --- | --- |
| `PostsService` | Post | get/list/create/update/delete + all/cursor/each + `builder(): PostBuilder` + `setMediaService()` |
| `PagesService` | Page | same minus builder |
| `CommentsService` | Comment | same |
| `UsersService` | User | same + `me(array\|QueryParametersInterface $params = []): User` |
| `MediaService` | Media | list/get/delete + `upload(string $filePath, array $attributes = []): Media` (multipart) + all/cursor/each — **no public update()** ⚠️ |
| `CategoriesService` / `TagsService` | Term | via `AbstractTermService` (endpoint-only override) |
| `SearchService` | SearchResult | list/search + all/cursor/each |
| `TaxonomiesService` / `PostTypesService` / `StatusesService` | Taxonomy/PostType/Status | `get(string $key, …)`, list + pagination, no interfaces |
| `SettingsService` | Settings | get/update — **constructed DTO manually, bypassing the decoder** ⚠️ |
| `ApplicationPasswordsService` | ApplicationPassword | list/get/create (typed), delete/deleteAll (raw); missing introspect + update subroutes ⚠️ |
| `DiscoveryService` | raw | index()/routes()/schema($path) via OPTIONS |
| `CustomEndpointService` | raw | get/post/put/patch/delete on `RestPath::normalize()`d paths |
| `RevisionsService` | raw | posts()/pages()/blocks() → `RevisionResourceService` (list/get/delete) |

**Raw (array-returning) services** — Plugins, Themes, Blocks (hand-rolled duplicate of the CRUD trait ⚠️), BlockTypes, BlockRenderer, BlockDirectory, MenuLocations, Navigations, NavMenus, NavMenuItems, Templates, TemplateParts, GlobalStyles, Widgets, WidgetTypes, Sidebars, SiteHealth.

**Duplication found (v4 fix list):**
1. `RawCrudById` / `RawCrudByStringId` traits near-identical (only id type differs) + `BlocksService` hand-rolled third copy → **one trait with `int|string $id`**.
2. Force-delete unwrap (`{deleted: true, previous: …}`) copy-pasted 5× → **one protected helper in `AbstractService`**.
3. `all()`/`cursor()`/`each()` copy-pasted in ~11 typed services → **one abstract `AbstractCollectionService`** with `fetchPage(array $query): PaginatedCollection` hook; `AbstractCrudService` (typed get/create/update/delete) and `AbstractStringKeyService` (get(string $key)) extend it.
4. `updateItem()` always POSTed; `putRaw` existed but was dead → decide per resource; POST stays for WP (WP accepts POST for updates), drop dead `putRaw`.
5. `SettingsService` manual DTO construction → route through the decoder like everything else.
6. `AbstractTermService` duplicated the typed CRUD surface → folded into `AbstractCrudService` (categories/tags are int-id CRUD like posts).

**Consumer-demanded fix (highest priority):** `MediaService::update()` must be **public** — the management app hacked into protected `AbstractService::updateItem` via `Closure::bind` because the SDK lacked it.

### 2.13 Support layer

- **`RestPath::normalize()`** — trim; empty → `''`; reject absolute URLs (`FILTER_VALIDATE_URL`) and `//`-prefixed; collapse `/+`; trim slashes. Keep as-is (v4: still in `Support`).
- **`PostBuilder`** (obtained via `posts()->builder()`, requires MediaService for images): fluent `title/content/excerpt/featuredImageId/featuredImage/categories/tags/status/slug/author` → `create()`; always sent `status=publish` + `format=standard`; no `update()`, no payload accessor, untyped `create(): object`. **v4: add `update(int $id)`, `toArray()`, typed `create(): Post`; keep publish default; drop forced `format` default.**
- **`ContentBuilder`** — Gutenberg markup generation: `text/heading/image/imageFromFile/quote/readMore/readMoreButton/pageBreak/toc/block/html`, containers `columns/group/buttons/button/separator`, `render()` (blocks joined by `\n\n`), `renderRaw()`, `static parse($content)`, `static fromHtml($html)`, `static registerBlock()`, `applyPattern(BlockPatternInterface)`.
- **Blocks** — `AbstractBlock` (render: `<!-- wp:{name}{attrs-json} -->\n{content}\n<!-- /wp:{name} -->`; empty attrs → no JSON segment; newlines only when content non-empty), `ContainerBlock` + `HasInnerBlocks` trait, `GenericBlock`, core blocks (Paragraph, Heading, Image, Quote, ReadMore, ReadMoreButton, PageBreak, Code, Shortcode, Separator, Button, Buttons, Column, Columns, Group), `HtmlBlock`, `TableOfContents` (simpletoc plugin).
- **`BlockRegistry`** — global singleton with static `reset()` (mutable global state leaking into tests). **v4: per-`ContentBuilder` instance registry.**
- **`BlockParser`** — round-trip parsing; ⚠️ **broken for any block whose `getName()` returns `core/…`** (Button/Buttons/Column/Columns/Group/Separator rendered `<!-- wp:core/button -->` — **invalid Gutenberg**); unknown blocks get a `core/` prefix injected (`myplugin/foo` → `core/myplugin/foo`); leaf reconstruction uses `strip_tags()` (data loss) + fragile hard-coded switch. **v4: bare block names everywhere (`wp:button`), registry lookup by bare name, no `core/` injection.**

**Scope watch (v1 docs, keep for v4):** `TableOfContents` (hard-codes `simpletoc/toc` + placeholder markup) and `BlockPatternInterface`/`applyPattern()` were flagged as extraction/deprecation candidates → **v4 drops both** ("SDK only — no plugin-specific helpers").

---

## 3. Consumers (What the SDK Was Used For)

### 3.1 `jooservices/wordpress-content-templates`

- Requires `jooservices/wordpress-sdk ^1.1 || ^1.2` + `jooservices/dto ^1.0`; pure payload composition, **no HTTP/auth/services reimplementation**.
- Builds on: `ContentBuilder` + core blocks (Paragraph/Heading/Image/Quote/Button/Buttons/Column/Columns/Group/Separator), raw post payload arrays (`title/content/status/featured_media/excerpt/tags/categories/meta`), `posts()->create()/pages()->create()` returning `Post`/`Page`, `RenderedContent` (reads `->raw ?? ->rendered`).
- **Lesson:** the SDK's block primitive set is the ceiling for template authors ("add block support to the SDK first"). ContentBuilder must stay in the SDK.

### 3.2 `wordpress-management` (Laravel app) + `laravel-wordpress`

- Standardizes on `WordPressService::create($url, $user, $appPassword, timeout, retries)` — **the facade factory is the API surface consumers lock onto**; must keep the exact name/shape in v4.
- Uses `posts()/pages()/categories()/tags()/media()/users()->me()/discovery()->index()`; reads `PaginatedCollection->total`/`->totalPages`; passes raw query arrays (not Query DTOs).
- Builds its own DTO layer for payloads because the SDK's `create()/update()` accept only raw arrays.

---

## 4. Versioning & Scope History (Context for v4)

| Version | Change |
| --- | --- |
| 1.1.0 | Application passwords, settings, discovery, custom endpoints services; raw admin/editor service group; `toArray()` redaction; `all()/cursor()/each()`; ContainerFactory fix |
| 1.2.0 (breaking) | **Removed** content templates + `PostsService::createFromTemplate()` → extracted to `wordpress-content-templates`; kept PostBuilder + ContentBuilder as "generic SDK-safe helpers"; Docker integration infra |

**Scope boundary (canonical, from v1 docs — v4 keeps it):**
- **SDK core:** native WordPress REST API client surface, typed DTOs, query DTOs, auth, pagination, discovery/schema helpers, error mapping, generic REST payload helpers (PostBuilder/ContentBuilder).
- **Not SDK:** content templates (stories/reviews/articles…), editorial publishing workflows, plugin/theme-specific logic.

**v4 addition to the boundary rules:** no dead contracts, no plugin-specific helpers (TableOfContents out), no container wiring inside the SDK (PHP-DI out — `WordPressService::create()` + constructor injection is enough; consumers who need a container bind it themselves).

---

## 5. Consumer Pain Points (v4 Must Fix)

1. **Media update missing** → `Closure::bind` hack into protected `updateItem()`. Fix: public `MediaService::update()`.
2. **`Page`/`Post` constructor fragility** — 23 required positional args; `TypeError` from uncast `template` (WP can return `false`). Fix: fully defaulted promoted constructors; named args; verify dto casting handles `false`→`string` or make `template` `string|false`.
3. **Errors not structured** — consumers string-matched `'unauthorized'`, `'401'`, `'timed out'`, `'could not resolve'`, `'json'`. Fix: client typed exceptions propagate (timeout/DNS/connect are already typed in client v4); SDK keeps HTTP-status taxonomy.
4. **Raw-array payloads** — consumers wanted typed payload DTOs. Fix: keep `array` payloads for `create()/update()` (WP payloads are inherently flexible; DTO-typed payloads are a documented roadmap item), but make everything *returned* typed.
5. **Query DTOs underused** — consumers re-implemented `page/per_page/search/status/orderby/order` mapping. Fix: keep Query DTOs simple + well-documented (they're the DX surface for typed querying).
6. **Testability** — no mockable service seam; consumers mocked concrete services. Fix: unit-test through the real request path with client `HttpFakeRegistry`; services stay final (not mockable by design).
7. **ContainerFactory trap** — defaults pointed at `https://example.com` with `user`/`pass`; resolving from DI produced a non-functional instance. Fix: no in-SDK container.
8. **Bearer/JWT auth** — consumer-side `AuthType` stored `jwt_bearer`/`bearer_token` but SDK couldn't validate. Fix: client v4 already provides `withBearerToken()`/custom middleware; the SDK documents how to build a custom client, keeps `withBasicAuth` for the standard path.
9. **`all()` memory risk** — documented; keep `cursor()`/`each()` as the recommended streaming path.

---

## 6. WordPress REST API Coverage (v1.2.0 Matrix → v4 Parity)

**Typed core (DTO + query DTO + typed CRUD):** posts, pages, media (list/get/upload/delete), comments, categories, tags, search (read), users (+`me`), settings (get/update), application-passwords (list/get/create; raw delete/deleteAll), taxonomies/types/statuses (read-only list/get by slug).

**Raw admin/editor groups (intentionally array-returning):** plugins, themes,
blocks, block-types, block-renderer, block-directory, menu-locations,
navigations, nav-menus, nav-menu-items, templates, template-parts,
global-styles, widget-types (+encode/render), widgets, sidebars, site-health,
revisions and autosaves for every core post-backed resource, block and pattern
directories, registered patterns/categories, pattern category terms, font
families/faces/collections, icons/collections, abilities, block-editor support,
view config, batch v1, and oEmbed.

**Completed v1 gaps in v4:** media update, application-password introspection,
block/pattern directories and categories, and revision/autosave subresources.
WordPress 7.1 exposes no per-UUID application-password update route.

**Remaining design work:** conditional ETag/Last-Modified helpers and typed
write payload DTOs. The Docker E2E compares live WordPress 7.1 discovery data
against `CoreRouteSupport` so unclassified default core route families fail.

---

## 7. v4 Design Decisions (Locked Recommendations)

### 7.1 Principles

SOLID, DRY, KISS, YAGNI are mandatory (workspace PHP standard). Concrete applications:

| Principle | Application |
| --- | --- |
| S | One responsibility per class; `ErrorMapper` maps, `ResponseDecoder` decodes, services orchestrate |
| O | Services implement narrow capability interfaces (`Readable/`, `Writable/`); client transport is swappable behind PSR-18 |
| L | `Page extends Post` only with defaulted ctor; Query DTOs share one `toQuery()` |
| I | No interface ships without an implementer; `me()` is a UsersService method, not a dead interface |
| D | Everything depends on interfaces: `ClientInterface` (PSR-18), `ResponseDecoderInterface`, `QueryParametersInterface` |
| DRY | One `AbstractCrudService` + one `AbstractCollectionService` + one `RawCrud` trait + one force-delete helper + one endpoint enum |
| KISS | No PHP-DI, no SDK auth middleware, no Symfony serializer/validator, no static logger singleton, no Link-header parsing |
| YAGNI | No `ContainerFactory`, no `BlockPatternInterface`, no `TableOfContents`, no nonexistent application-password update route, no content templates |

### 7.2 PHP 8.5 usage

`declare(strict_types=1)` everywhere; readonly classes/properties; promoted constructor props; backed enums (`Endpoint`); `match` in ErrorMapper; DNF unions (`array|QueryParametersInterface|null`); generators for `cursor()`; `array_is_list()`; `#[\Override]` on overrides; `JsonException` via `JSON_THROW_ON_ERROR`. No property hooks, no pipe operator unless clearly clearer (KISS decides). Final classes unless extension is documented.

### 7.3 PSR compliance

- PSR-1/PSR-4: mandatory; namespace `JOOservices\WordPress\Sdk\` → `src/`.
- PSR-7: requests/responses throughout the transport boundary.
- PSR-18: services type against `Psr\Http\Client\ClientInterface` (v1 typed against the client's own `HttpClientInterface` — v4 upgrades).
- PSR-3: `?LoggerInterface` injected where logging is needed (decoder, client builder).
- PSR-17: `StreamFactoryInterface` for multipart body construction and RequestBuilder streams.
- PSR-12/PER-CS 3.0: Pint `per` preset (v1 used `psr12` — stale).
- PSR-11: no container dependency shipped (YAGNI).

### 7.4 Tooling stack (v4)

| Tool | Config | Notes |
| --- | --- | --- |
| Docker | php:8.5-cli-bookworm image `jooservices/wordpress-sdk:php85` | the workspace pattern, §1.3 |
| Pint | `per` preset | workspace mandate; v1 used psr12 |
| PHPStan | level **max**, src + tests, `phpstan-phpunit` | v1: level 9 src-only |
| PHPUnit | ^13, Unit suite, random order, strict flags, pcov coverage | v1: ^12.5 |
| captainhook | opt-in pre-commit: pint + phpstan; pre-push: phpunit + audit | never installed automatically for CI/consumers |
| PHPCS/PHPMD | **dropped** | v1 ran them with heavy suppression noise; Pint + PHPStan max covers style + static correctness |
| composer scripts | `lint` (pint --test, phpstan), `test`, `test:coverage`, `check` (lint+test), `ci` (lint + test:coverage + audit), `security` (composer audit) | — |

### 7.5 Testing strategy (v4)

- **Unit tests through the real request path:** build the SDK client via `ClientFactory`, fake HTTP with `ClientBuilder::fake()` / `HttpFakeRegistry::respond(method, pattern, sequence)`, assert with `recorded()` (method, URI, query, headers) and `TestResponse` payloads. No `NullHttpClient`/`RecordsServiceRequests` test doubles (v1 hacks deleted).
- DTO hydration tests exercise `jooservices/dto` `from()` with realistic WP payloads (including `false` template, unknown keys, string ids, nested `RenderedContent`).
- ContentBuilder tests assert exact Gutenberg markup + parser round-trips (with the naming fixes).
- Integration tests run against disposable Docker WordPress 7.1, MariaDB, and
  WP-CLI. They verify authentication, live route-family completeness, and
  representative CRUD/media/autosave/batch workflows.

### 7.6 Composer require for v4

```json
{
  "require": {
    "php": "^8.5",
    "jooservices/client": "^4.2",
    "jooservices/dto": "^3.2",
    "psr/log": "^3.0"
  }
}
```

Dropped vs v1: symfony/serializer, symfony/property-info, symfony/property-access, symfony/validator, monolog/monolog, php-di/php-di (all replaced by dto engine + client + PSR-3 interface).

---

## 8. Verified Pre-requisites (All Resolved — Re-checked Aug 2026)

The upstream blockers were fixed and re-verified with probes in Docker:

| # | Question | Status |
| --- | --- | --- |
| 1 | dto hydration: plain `array`, `list<int>`, `array<string,mixed>`, `array<string,string>` | ✅ Fixed in dto v3.2.0 — full WP-like payload hydrates correctly (`false`→`''`, `'42'`→42, unknown keys ignored, nested `RenderedContent` OK) |
| 2 | dto `@param` on constructor vs per-property `@var` | ✅ Fixed in dto v3.2.0 — constructor `@param` tags are read |
| 3 | dto `DataCollection::toArray()` | ✅ Added in v3.2.0 |
| 4 | Multipart upload | ✅ Fixed in client v4.2.0 — `RequestBuilder::withMultipart()` + `MultipartPart` + `PreparedRequest`; SDK `MediaService::upload()` uses the client builder directly |
| 5 | Base URI trailing slash (`https://site/wp-json` vs `…/wp-json/`) | ✅ Fixed in client v4.2.0 — `BaseUri::normalize()` before resolution; SDK `Config` still normalizes to a trailing slash (defense in depth) |
| 6 | `ClientBuilder::fake()` static state isolation | ✅ Use `InteractsWithHttpClient` trait (`setUpHttpFakes()`/`tearDownHttpFakes()`) — no fix needed |
| 7 | Retry defaults (`RetryConfig`: GET/HEAD/PUT/DELETE/OPTIONS on 408/425/429/5xx) | ✅ Accepted as SDK default; POST excluded (safe) |
| 8 | `MappingException` on missing required property; `CastException: Null is not allowed` | ✅ By design — SDK rule: every DTO constructor param has a default; nullable types where WP can send `null` |

> [!IMPORTANT] Hydration probe result (dto v3.2.0)
> Re-verified with a full WP-like payload: `id '42'→42`, `categories ['1',2,3]→[1,2,3]`, `meta`/`avatar_urls` assoc arrays pass through, `featured_media null→null`, `template false→''`, `_links` ignored, nested `RenderedContent` hydrates, `collection()` + `toArray()` work. The earlier `CastException: No caster matched the value` (v3.1.0) is gone.

### Remaining SDK-side rules (locked)

- Every DTO constructor parameter must have a default value (`int $id = 0`, `string $name = ''`, `array $x = []`, `?string $y = null`).
- Use nullable types for fields WP can omit with `null` (`?int $featured_media`).
- Media upload uses the client's `RequestBuilder::withMultipart()` (no SDK multipart code).
- `Config::normalizeBaseUrl()` keeps the trailing-slash rule even though the client now normalizes.

---

## 9. Suggested Build Order (when implementation starts)

1. Skeleton: Dockerfile, docker-compose.yml, Makefile, composer.json (client ^4.2, dto ^3.2), pint/phpstan/phpunit/captainhook configs, LICENSE/SECURITY/CONTRIBUTING/CHANGELOG, AGENTS.md, README.md.
3. Knowledge-adjacent docs (`docs/00-knowledge/`, getting-started, user guide, development, maintenance/coverage matrix).
4. Core: `Endpoint` enum, `Exceptions`, `Contracts`, `Config`, `Http/*` (RequestBuilder with query, ResponseDecoder on dto engine, ErrorMapper, AbstractService, ClientFactory, media upload via client `withMultipart`), `Pagination\PaginatedCollection`.
5. Data: DTOs + Query DTOs.
6. Services: `AbstractCollectionService`, `AbstractCrudService`, `AbstractStringKeyService`, `RawEndpointService` + `RawCrud` trait, then concrete services.
7. Support: `RestPath`, `PostBuilder`, `ContentBuilder` (blocks + parser, naming fixes, per-instance registry).
8. `WordPressService` facade (registry-based lazy getters, `create()`/`fromConfig()`).
9. Tests (fake-HTTP) + coverage gate.
10. Verify in Docker: `composer ci`.

---

## 10. Sources

- `archives/JOOservices.2/wordpress-sdk` — v1.2.0+ source, docs, tests, docker/wordpress integration infra.
- `archives/JOOservices.2/wordpress-content-templates` — SDK consumer #1.
- `archives/JOOservices.2/wordpress-management` + `archives/JOOservices.2/laravel-wordpress` (in archive root) — SDK consumer #2, handoff.md lessons.

## 11. Implementation status update — 2026-09-01

This update supersedes earlier roadmap statements in this historical knowledge
base. The current tree implements the WordPress 7.1 default core REST route
families, including patterns, autosaves/revisions, fonts, icons, abilities,
editor support, batch, and oEmbed. `CoreRouteSupport` is reconciled against the
live discovery map in a disposable Docker + WP-CLI E2E. The verified Unit gate
is 96.70% statement coverage with no zero-covered production API.
- Workspace: `client/` (v4.1.0), `dto/` (v3.1.0), `docs/02-engineering/02-quality/php-language-standard.md`, root `AGENTS.md`.
