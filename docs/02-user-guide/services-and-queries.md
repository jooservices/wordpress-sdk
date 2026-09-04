# Services and queries

## The facade

`WordPressService` is the single entry point. Services are lazily created on
first access and cached per facade instance.

| Service | Return types | Notes |
| --- | --- | --- |
| `posts()` | `Post`, `PaginatedCollection<Post>`, `PostBuilder` | full CRUD |
| `pages()` | `Page`, `PaginatedCollection<Page>` | full CRUD |
| `comments()` | `Comment`, `PaginatedCollection<Comment>` | full CRUD |
| `users()` | `User`, `PaginatedCollection<User>` | full CRUD + `me()` |
| `media()` | `Media`, `PaginatedCollection<Media>` | CRUD + `upload()` |
| `categories()` / `tags()` | `Term`, `PaginatedCollection<Term>` | full CRUD |
| `search()` | `PaginatedCollection<SearchResult>` | read-only |
| `taxonomies()` / `postTypes()` / `statuses()` | keyed DTOs | get by slug, read-only |
| `settings()` | `Settings` | get/update |
| `applicationPasswords()` | `ApplicationPassword` | scoped by user id |
| `discovery()` | raw arrays | index/routes/schema |
| `custom()` | raw arrays | arbitrary relative paths |
| `revisions()` / `autosaves()` | raw arrays | shared post-backed allowlist via `resource()` |
| `plugins()` / `themes()` / `blocks()` / `blockTypes()` / `blockRenderer()` / `blockDirectory()` / `menuLocations()` / `navigations()` / `navMenus()` / `navMenuItems()` / `templates()` / `templateParts()` / `globalStyles()` / `widgets()` / `widgetTypes()` / `sidebars()` / `siteHealth()` | raw arrays | admin/editor groups |
| `patterns()` / `fonts()` / `icons()` / `abilities()` | raw arrays | WordPress 7.1 registry and mutation groups |
| `editor()` / `utility()` | raw arrays | editor support, batch, and oEmbed routes |

## Query DTOs

List/read operations accept `array<string, mixed>` or a typed query DTO
(`Data\Query\*`). Query DTOs map camelCase parameters to WordPress snake_case
keys and drop unset values:

```php
use JOOservices\WordPress\Sdk\Data\Query\ListPostsQuery;

$posts = $wordpress->posts()->list(new ListPostsQuery(
    author: [1, 2],
    categories: [5],
    status: 'publish',
    perPage: 20,
    orderby: 'date',
    order: 'desc',
    fields: 'id,title',
));
```

All query DTOs share: `page`, `perPage`, `search`, `context`, `orderby`,
`order`, `include`, `exclude`, `fields` (→ `_fields`), `embed` (→ `_embed`).

## Raw custom endpoints

Paths are relative to the configured REST root. Absolute URLs are rejected.

```php
$items = $wordpress->custom()->get('my-plugin/v1/items', ['page' => 1]);
$item = $wordpress->custom()->post('my-plugin/v1/items', ['name' => 'Example']);
$wordpress->custom()->delete('my-plugin/v1/items/1');
```
