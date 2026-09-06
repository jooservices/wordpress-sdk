<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Data\Write;

use JOOservices\Dto\Core\Dto;
use JOOservices\WordPress\Sdk\Contracts\Writable\PayloadInterface;
use JOOservices\WordPress\Sdk\Support\MapsScalarQuery;

/**
 * Typed create/update body for application passwords.
 */
final class ApplicationPasswordPayload extends Dto implements PayloadInterface
{
    use MapsScalarQuery;

    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $appId = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toPayload(): array
    {
        return $this->omitEmpty([
            'name' => $this->name,
            'app_id' => $this->appId,
        ]);
    }
}
