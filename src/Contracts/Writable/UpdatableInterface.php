<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Contracts\Writable;

/**
 * @template TDto of object
 */
interface UpdatableInterface
{
    /**
     * @param array<string, mixed> $payload
     *
     * @return TDto
     */
    public function update(int $id, array $payload): object;
}
