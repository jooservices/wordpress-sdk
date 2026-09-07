<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Data;

use JOOservices\Dto\Core\Dto;

/**
 * The `{raw, rendered, protected}` object WordPress embeds in title, content,
 * excerpt, guid, caption, and description fields.
 */
final class RenderedContent extends Dto
{
    public function __construct(
        public readonly string $rendered = '',
        public readonly bool $protected = false,
        public readonly ?string $raw = null,
    ) {}
}
