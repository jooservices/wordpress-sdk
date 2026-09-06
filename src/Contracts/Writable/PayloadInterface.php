<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Contracts\Writable;

/**
 * A typed write payload that serializes to a WordPress REST body.
 */
interface PayloadInterface
{
    /**
     * @return array<string, mixed>
     */
    public function toPayload(): array;
}
