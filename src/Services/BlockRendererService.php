<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Services;

use JOOservices\WordPress\Sdk\Endpoints\Endpoint;
use JOOservices\WordPress\Sdk\Support\RestPath;

/**
 * Server-side block rendering (`/wp/v2/block-renderer/{name}`).
 *
 * Requests are sent with `context=edit` because the renderer route validates
 * dynamic blocks against editor-only route context.
 */
final class BlockRendererService extends RawEndpointService
{
    /**
     * @param array<string, mixed> $attributes
     *
     * @return array<string, mixed>
     */
    public function render(string $name, array $attributes = [], ?int $postId = null): array
    {
        $query = ['context' => 'edit'];

        if ($attributes !== []) {
            $query['attributes'] = $attributes;
        }

        if ($postId !== null) {
            $query['post_id'] = $postId;
        }

        return $this->getRaw(
            Endpoint::BLOCK_RENDERER->path() . '/' . (new RestPath())->normalize($name),
            $query,
        );
    }
}
