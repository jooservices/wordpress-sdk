<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Services;

use JOOservices\WordPress\Sdk\Data\Settings;
use JOOservices\WordPress\Sdk\Endpoints\Endpoint;
use JOOservices\WordPress\Sdk\Http\AbstractService;

/**
 * Site settings (`/wp/v2/settings`). Settings are a dynamic key/value map
 * hydrated through `Settings::from()` like every other DTO.
 */
final class SettingsService extends AbstractService
{
    public function get(): Settings
    {
        /** @var Settings */
        return $this->getItem(Endpoint::SETTINGS->path(), Settings::class);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function update(array $payload): Settings
    {
        /** @var Settings */
        return $this->createItem(Endpoint::SETTINGS->path(), $payload, Settings::class);
    }
}
