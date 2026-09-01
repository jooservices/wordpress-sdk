<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Data;

use JOOservices\Dto\Core\Dto;

/**
 * WordPress site settings (`/wp/v2/settings`).
 *
 * Settings are a dynamic key/value map. Constructed directly from the raw
 * response instead of through the hydration engine.
 */
final class Settings extends Dto
{
    /**
     * @param array<string, mixed> $values
     */
    public function __construct(
        public readonly array $values = [],
    ) {}

    public function get(string $key, mixed $default = null): mixed
    {
        return array_key_exists($key, $this->values) ? $this->values[$key] : $default;
    }
}
