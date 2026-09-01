# Pagination

## Pages

`list()` returns `PaginatedCollection<T>` with the WordPress totals:

```php
$media = $wordpress->media()->list(['per_page' => 20, 'page' => 2]);

printf(
    "Loaded %d of %d items across %d pages\n",
    count($media),
    $media->total,
    $media->totalPages,
);
```

The collection is iterable and countable; `all()` returns the raw item list.

## Streaming

Every collection service provides three helpers over the paginated list:

| Helper | Behavior |
| --- | --- |
| `cursor($params)` | lazy `Generator` — one HTTP page per iteration step |
| `each($callback, $params)` | iterates `cursor()`; `return false` stops early |
| `all($params)` | loads every page into memory — use with care |

```php
foreach ($wordpress->posts()->cursor(['per_page' => 50]) as $post) {
    // One request per page, streamed.
}

$wordpress->posts()->each(function ($post): bool {
    if ($post->status !== 'publish') {
        return false; // stop iterating
    }
    return true;
});
```

## Pagination and query DTOs

`page` and `perPage` are part of every query DTO, so `cursor()` composes with
typed queries:

```php
use JOOservices\WordPress\Sdk\Data\Query\ListPostsQuery;

$query = new ListPostsQuery(status: 'publish', perPage: 20);
foreach ($wordpress->posts()->cursor($query) as $post) {
    // ...
}
```