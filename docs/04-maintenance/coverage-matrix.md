# WordPress REST API coverage matrix

## Typed (DTO + query DTO)

| Route | Service | Read | Write | Notes |
| --- | --- | --- | --- | --- |
| `/wp/v2/posts` | `posts()` | get/list/all/cursor/each | create/update/delete | `Post`, `ListPostsQuery` |
| `/wp/v2/pages` | `pages()` | same | same | `Page extends Post` |
| `/wp/v2/media` | `media()` | same | create via `upload()`, update/delete | `Media`, `ListMediaQuery`, multipart |
| `/wp/v2/comments` | `comments()` | same | same | `Comment`, `ListCommentsQuery` |
| `/wp/v2/categories` | `categories()` | same | same | `Term`, `ListTermsQuery` |
| `/wp/v2/tags` | `tags()` | same | same | `Term`, `ListTermsQuery` |
| `/wp/v2/search` | `search()` | list/search | — | `SearchResult`, `SearchQuery` |
| `/wp/v2/users` + `/users/me` | `users()` | same + `me()` | create/update/delete | `User`, `ListUsersQuery` |
| `/wp/v2/settings` | `settings()` | get | update | `Settings` (dynamic map) |
| `/wp/v2/users/{id}/application-passwords` | `applicationPasswords()` | list/get | create/delete/deleteAll | `ApplicationPassword`; generated secret only on create |
| `/wp/v2/taxonomies` | `taxonomies()` | get/list/all/cursor/each | — | assoc payloads keyed by slug |
| `/wp/v2/types` | `postTypes()` | same | — | |
| `/wp/v2/statuses` | `statuses()` | same | — | |

## Raw arrays (admin/editor groups)

`plugins`, `themes`, `blocks`, `block-types`, `block-renderer`,
`block-directory/search`, `menu-locations`, `navigation`, `menus`,
`menu-items`, `templates`, `template-parts`, `global-styles`, `widgets`,
`widget-types` (+ `encode`/`render`), `sidebars`, all
`wp-site-health/v1` tests, revisions and autosaves for the shared post-backed
allowlist (`posts`, `pages`, `blocks`, `templates`, `template-parts`,
`navigation`, `menu-items`), block/pattern directories, registered
patterns/categories, pattern category terms, font families/faces/collections,
icons/collections, abilities, block-editor support routes, view config, batch
v1, and oEmbed.

## Discovery and custom routes

- `discovery()`: `GET <root>` index, routes map, `OPTIONS <path>` schema.
- `custom()`: any HTTP verb on relative paths (`RestPath`-normalized; absolute
  URLs rejected).

## Completeness and remaining design work

- `CoreRouteSupport` matches live discovery routes to SDK-covered patterns
  derived from `Endpoint` plus known nested subresources (not bare namespace
  prefixes). An unknown default core route fails E2E until the SDK declares it.
- Revisions and autosaves share one post-backed resource allowlist
  (`PostBackedResources`).
- Application passwords have no per-UUID update route in WordPress 7.1; the
  supported core operations are list/get/create/introspect/delete/delete-all.
- ETag/Last-Modified conditional request helpers.
- Typed payload DTOs for `create()`/`update()` (payloads stay raw arrays).

All WordPress 7.1 default core route families discovered by the live Docker
environment are classified above. This is an executable completeness check,
not a planned integration.
