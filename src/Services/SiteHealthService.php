<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Services;

use JOOservices\WordPress\Sdk\Endpoints\Endpoint;

/**
 * Site Health diagnostic tests (`/wp-site-health/v1/tests/{test}`).
 */
final class SiteHealthService extends RawEndpointService
{
    /**
     * @return array<string, mixed>
     */
    public function test(string $test): array
    {
        return $this->getRaw(Endpoint::SITE_HEALTH->withKey($test));
    }

    /**
     * @return array<string, mixed>
     */
    public function backgroundUpdates(): array
    {
        return $this->test('background-updates');
    }

    /**
     * @return array<string, mixed>
     */
    public function loopbackRequests(): array
    {
        return $this->test('loopback-requests');
    }

    /**
     * @return array<string, mixed>
     */
    public function httpsStatus(): array
    {
        return $this->test('https-status');
    }

    /** @return array<string, mixed> */
    public function dotOrgCommunication(): array
    {
        return $this->test('dotorg-communication');
    }
    /** @return array<string, mixed> */
    public function authorizationHeader(): array
    {
        return $this->test('authorization-header');
    }
    /** @return array<string, mixed> */
    public function pageCache(): array
    {
        return $this->test('page-cache');
    }
    /** @return array<string, mixed> */
    public function directorySizes(): array
    {
        return $this->getRaw('wp-site-health/v1/directory-sizes');
    }
}
