<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Services;

use JOOservices\WordPress\Sdk\Endpoints\Endpoint;

/** WordPress 7 icon and icon-collection discovery APIs. */
final class IconsService extends RawEndpointService
{
    /** @return array<string, mixed> */
    public function list(?string $collection = null): array
    {
        return $this->getRaw($collection === null ? Endpoint::ICONS->path() : Endpoint::ICONS->withKey($this->segment($collection)));
    }
    /** @return array<string, mixed> */
    public function get(string $collection, string $name): array
    {
        return $this->getRaw(Endpoint::ICONS->withValues([$collection, $name]));
    }
    /** @return array<string, mixed> */
    public function collections(): array
    {
        return $this->getRaw(Endpoint::ICON_COLLECTIONS->path());
    }
    /** @return array<string, mixed> */
    public function collection(string $slug): array
    {
        return $this->getRaw(Endpoint::ICON_COLLECTIONS->withKey($this->segment($slug)));
    }
}
