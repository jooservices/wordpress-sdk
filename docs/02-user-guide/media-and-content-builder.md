# Media and content builder

## Media

```php
// Upload (multipart/form-data)
$media = $wordpress->media()->upload('/tmp/photo.png', [
    'title' => 'Sunset',
    'alt_text' => 'Sunset over the bay',
    'status' => 'inherit',
]);

// Update metadata (public since v4 — the v1 gap consumers hacked around)
$wordpress->media()->update($media->id, ['alt_text' => 'Corrected alt']);

// Query
$images = $wordpress->media()->list(new ListMediaQuery(mediaType: 'image', perPage: 20));
```

## ContentBuilder

`ContentBuilder` generates Gutenberg comment-delimited block markup:

```php
$builder = $wordpress->contentBuilder(); // media-wired for uploads

$content = $builder
    ->heading('Chapter 1', 3, ['anchor' => 'chapter-1'])
    ->text('Some body text')
    ->quote('To be', 'Shakespeare')
    ->columns([
        fn ($column) => $column->text('Left'),
        fn ($column) => $column->text('Right'),
    ])
    ->buttons([
        ['text' => 'Read', 'url' => 'https://example.com'],
    ])
    ->imageFromFile('/tmp/photo.png', ['alt_text' => 'Sunset'])
    ->readMore('Continue reading')
    ->render();
```

Block names serialize without the `core/` prefix (`wp:button`, not
`wp:core/button`). Empty-content blocks self-close (`<!-- wp:read-more /-->`).
Container blocks embed their inner blocks' markup, matching the Gutenberg
serializer.

`ContentBuilder::parse()` round-trips markup back into blocks; unknown or
plugin blocks degrade to `GenericBlock` (no data loss). Custom block classes
register per builder via `registerBlock()` / `BlockRegistry`.

## PostBuilder

Fluent post payload assembly, obtained from `posts()->builder()`:

```php
$post = $wordpress->posts()->builder()
    ->title('Article')
    ->content($content) // ContentBuilder | Closure | raw string
    ->excerpt('Teaser')
    ->featuredImageId($media->id) // or featuredImage($path) to upload
    ->categories([5])
    ->tags([9])
    ->status('draft')
    ->slug('article')
    ->create();

// Update later with extra fields:
$wordpress->posts()->builder()
    ->title('Renamed')
    ->update($post->id, ['status' => 'publish']);
```

`toArray()` exposes the assembled payload.