<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Services;

use JOOservices\WordPress\Sdk\Data\Settings;
use JOOservices\WordPress\Sdk\Endpoints\Endpoint;
use JOOservices\WordPress\Sdk\Http\AbstractService;

/**
 * Site settings (`/wp/v2/settings`). Settings are a dynamic key/value map,
 * so the DTO is constructed directly from the raw response.
 */
final class SettingsService extends AbstractService
{
    public function get(): Settings
    {
        /** @var array<string, mixed> $data */
        $data = $this->requestArray('GET', Endpoint::SETTINGS->path());

        return new Settings($data);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function update(array $payload): Settings
    {
        /** @var array<string, mixed> $data */
        $data = $this->requestArray('POST', Endpoint::SETTINGS->path(), ['body' => $payload]);

        return new Settings($data);
    }
}
