<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Services;

/** Block-editor support routes registered by WordPress core. */
final class EditorService extends RawEndpointService
{
    /** @return array<string, mixed> */
    public function urlDetails(string $url): array
    {
        return $this->getRaw('wp-block-editor/v1/url-details', ['url' => $url]);
    }
    /** @return array<string, mixed> */
    public function export(): array
    {
        return $this->getRaw('wp-block-editor/v1/export');
    }
    /** @return array<string, mixed> */
    public function navigationFallback(): array
    {
        return $this->getRaw('wp-block-editor/v1/navigation-fallback');
    }
    /** @return array<string, mixed> */
    public function viewConfig(): array
    {
        return $this->getRaw('wp/v2/view-config');
    }
}
