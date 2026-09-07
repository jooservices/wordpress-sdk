<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Data\Write;

use JOOservices\Dto\Core\Dto;
use JOOservices\WordPress\Sdk\Contracts\Writable\PayloadInterface;
use JOOservices\WordPress\Sdk\Support\MapsScalarQuery;

/**
 * Typed create/update body for taxonomy terms.
 */
final class TermPayload extends Dto implements PayloadInterface
{
    use MapsScalarQuery;

    /**
     * @param array<string, mixed>|null $meta
     */
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $slug = null,
        public readonly ?string $description = null,
        public readonly ?int $parent = null,
        public readonly ?array $meta = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toPayload(): array
    {
        return $this->omitEmpty([
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'parent' => $this->parent,
            'meta' => $this->meta,
        ]);
    }
}
