<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Contracts\Writable;

/**
 * @template TDto of object
 */
interface CreatableInterface
{
    /**
     * @param array<string, mixed> $payload
     *
     * @return TDto
     */
    public function create(array $payload): object;
}
