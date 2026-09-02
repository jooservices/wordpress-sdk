<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Services;

use JOOservices\WordPress\Sdk\Endpoints\Endpoint;

/**
 * Menu location reads (`/wp/v2/menu-locations`).
 */
final class MenuLocationsService extends RawEndpointService
{
    /**
     * @return array<string, mixed>
     */
    public function list(): array
    {
        return $this->getRaw(Endpoint::MENU_LOCATIONS->path());
    }

    /**
     * @return array<string, mixed>
     */
    public function get(string $location): array
    {
        return $this->getRaw(Endpoint::MENU_LOCATIONS->withKey($location));
    }
}
