<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Data;

use JOOservices\Dto\Core\Dto;

/**
 * WordPress site settings (`/wp/v2/settings`).
 *
 * Settings are a dynamic key/value map. `from()` always treats the input as
 * that flat WordPress map (including a setting key named `values`).
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

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    #[\Override]
    protected static function transformInput(array $data): array
    {
        return ['values' => $data];
    }

    /**
     * Expose the WordPress flat map (not the DTO property envelope).
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    #[\Override]
    protected function beforeSerialization(array $data): array
    {
        $values = $data['values'] ?? [];
        if (! is_array($values)) {
            return [];
        }

        /** @var array<string, mixed> $values */
        return $values;
    }
}
