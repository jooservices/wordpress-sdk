<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Data;

use JOOservices\Dto\Core\Dto;

/**
 * WordPress site settings (`/wp/v2/settings`).
 *
 * Settings are a dynamic key/value map. WordPress returns a flat object;
 * {@see transformInput()} wraps it for `from()` hydration.
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
        if (
            array_key_exists('values', $data)
            && is_array($data['values'])
            && array_diff_key($data, ['values' => true]) === []
        ) {
            return $data;
        }

        return ['values' => $data];
    }
}
