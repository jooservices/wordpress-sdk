<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Services;

use JOOservices\WordPress\Sdk\Endpoints\Endpoint;

/** Block-editor support routes registered by WordPress core. */
final class EditorService extends RawEndpointService
{
    /** @return array<string, mixed> */
    public function urlDetails(string $url): array
    {
        return $this->getRaw(Endpoint::EDITOR_URL_DETAILS->path(), ['url' => $url]);
    }

    /** @return array<string, mixed> */
    public function export(): array
    {
        return $this->getRaw(Endpoint::EDITOR_EXPORT->path());
    }

    /** @return array<string, mixed> */
    public function navigationFallback(): array
    {
        return $this->getRaw(Endpoint::EDITOR_NAVIGATION_FALLBACK->path());
    }

    /** @return array<string, mixed> */
    public function viewConfig(): array
    {
        return $this->getRaw(Endpoint::VIEW_CONFIG->path());
    }
}
