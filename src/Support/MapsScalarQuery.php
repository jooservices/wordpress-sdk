<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Support;

use BackedEnum;

/**
 * Maps backed enums and empty values for WordPress query/body arrays.
 */
trait MapsScalarQuery
{
    /**
     * @param BackedEnum|bool|float|int|string|null $value
     */
    protected function scalar(mixed $value): bool|float|int|string|null
    {
        return $value instanceof BackedEnum ? $value->value : $value;
    }

    /**
     * @param array<string, mixed> $values
     *
     * @return array<string, mixed>
     */
    protected function omitEmpty(array $values): array
    {
        return array_filter(
            $values,
            static fn(mixed $value): bool => $value !== null && $value !== [],
        );
    }
}
