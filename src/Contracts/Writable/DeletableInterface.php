<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Contracts\Writable;

/**
 * @template TDto of object
 */
interface DeletableInterface
{
    /**
     * @return TDto
     */
    public function delete(int $id, bool $force = false): object;
}
