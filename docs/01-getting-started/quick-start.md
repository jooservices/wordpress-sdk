# Quick start

```php
use JOOservices\WordPress\Sdk\Data\Query\ListPostsQuery;
use JOOservices\WordPress\Sdk\WordPressService;

$wordpress = WordPressService::create(
    baseUrl: getenv('WORDPRESS_URL'),
    username: getenv('WORDPRESS_USER'),
    password: getenv('WORDPRESS_APP_PASSWORD'),
);

$posts = $wordpress->posts()->list(new ListPostsQuery(
    perPage: 10,
    search: 'automation',
    fields: 'id,title,link,date',
    embed: true,
));

foreach ($posts as $post) {
    printf(
        "%d: %s — %d of %d items\n",
        $post->id,
        $post->title?->rendered,
        count($posts),
        $posts->total,
    );
}
```

## Reading a single item

```php
$post = $wordpress->posts()->get(42);
echo $post->content?->rendered;
```

## Creating content

```php
$post = $wordpress->posts()->create([
    'title' => 'Hello World',
    'content' => '<!-- wp:paragraph --><p>Body</p><!-- /wp:paragraph -->',
    'status' => 'draft',
]);
```

## Authentication checks

```php
$me = $wordpress->users()->me();
echo $me->name;
```

## What's next

- [Services and queries](../02-user-guide/services-and-queries.md)
- [Error handling](../02-user-guide/authentication-and-errors.md)
- [Pagination](../02-user-guide/pagination.md)
