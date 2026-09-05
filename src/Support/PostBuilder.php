<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Support;

use Closure;
use JOOservices\WordPress\Sdk\Data\Post;
use JOOservices\WordPress\Sdk\Services\MediaService;
use JOOservices\WordPress\Sdk\Services\PostsService;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\ContentBuilder;
use RuntimeException;

/**
 * Fluent post payload builder.
 *
 * Create with {@see PostsService::builder()}; uploads via
 * `featuredImage()` require the posts service to be wired with a
 * `MediaService` (done automatically by the facade).
 */
final class PostBuilder
{
    /**
     * @var array<string, mixed>
     */
    private array $data = [];

    public function __construct(
        private readonly PostsService $postsService,
        private readonly ?MediaService $mediaService = null,
    ) {}

    public function title(string $title): self
    {
        $this->data['title'] = $title;

        return $this;
    }

    /**
     * Accepts rendered block markup (`render()` output), a raw string, or a
     * closure that receives a fresh `ContentBuilder` and returns it.
     */
    public function content(ContentBuilder|Closure|string $content): self
    {
        if ($content instanceof ContentBuilder) {
            $this->data['content'] = $content->render();

            return $this;
        }

        if ($content instanceof Closure) {
            $builder = new ContentBuilder();
            if ($this->mediaService !== null) {
                $builder->setMediaService($this->mediaService);
            }

            $built = $content($builder);
            if (! $built instanceof ContentBuilder) {
                throw new RuntimeException('The content closure must return a ContentBuilder instance.');
            }

            $this->data['content'] = $built->render();

            return $this;
        }

        $this->data['content'] = $content;

        return $this;
    }

    public function excerpt(string $excerpt): self
    {
        $this->data['excerpt'] = $excerpt;

        return $this;
    }

    public function featuredImageId(int $mediaId): self
    {
        $this->data['featured_media'] = $mediaId;

        return $this;
    }

    /**
     * Uploads a file and uses it as the featured image.
     *
     * @param array<string, mixed> $attributes upload attributes
     */
    public function featuredImage(string $filePath, array $attributes = []): self
    {
        if ($this->mediaService === null) {
            throw new RuntimeException('MediaService is not available. Cannot upload images.');
        }

        $media = $this->mediaService->upload($filePath, $attributes);

        return $this->featuredImageId($media->id);
    }

    /**
     * @param list<int> $ids
     */
    public function categories(array $ids): self
    {
        $this->data['categories'] = $ids;

        return $this;
    }

    /**
     * @param list<int> $ids
     */
    public function tags(array $ids): self
    {
        $this->data['tags'] = $ids;

        return $this;
    }

    public function status(string $status): self
    {
        $this->data['status'] = $status;

        return $this;
    }

    public function slug(string $slug): self
    {
        $this->data['slug'] = $slug;

        return $this;
    }

    public function author(int $authorId): self
    {
        $this->data['author'] = $authorId;

        return $this;
    }

    public function create(): Post
    {
        if (! isset($this->data['title'])) {
            throw new RuntimeException('Post title is required.');
        }

        /** @var Post */
        return $this->postsService->create($this->payload());
    }

    /**
     * @param array<string, mixed> $extra additional payload fields merged
     *                                    into the update
     */
    public function update(int $id, array $extra = []): Post
    {
        /** @var Post */
        return $this->postsService->update($id, [...$this->payload(), ...$extra]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->payload();
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        if (! isset($this->data['status'])) {
            return [...$this->data, 'status' => 'publish'];
        }

        return $this->data;
    }
}
