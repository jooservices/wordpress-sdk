<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Services;

use JOOservices\WordPress\Sdk\Endpoints\Endpoint;
use JOOservices\WordPress\Sdk\Support\RestPath;

/** WordPress 7 abilities and ability-category APIs. */
final class AbilitiesService extends RawEndpointService
{
    /**
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     */
    public function list(array $query = []): array
    {
        return $this->getRaw(Endpoint::ABILITIES->path(), $query);
    }
    /** @return array<string, mixed> */
    public function get(string $name): array
    {
        return $this->getRaw(Endpoint::ABILITIES->path() . '/' . (new RestPath())->normalize($name));
    }
    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function run(string $name, array $input = []): array
    {
        return $this->postRaw(Endpoint::ABILITIES->path() . '/' . (new RestPath())->normalize($name) . '/run', ['input' => $input]);
    }
    /** @return array<string, mixed> */
    public function categories(): array
    {
        return $this->getRaw(Endpoint::ABILITY_CATEGORIES->path());
    }
    /** @return array<string, mixed> */
    public function category(string $slug): array
    {
        return $this->getRaw(Endpoint::ABILITY_CATEGORIES->withKey($this->segment($slug)));
    }
}
