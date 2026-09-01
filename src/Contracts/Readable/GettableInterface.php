<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Contracts\Readable;

use JOOservices\WordPress\Sdk\Contracts\QueryParametersInterface;

/**
 * @template TDto of object
 */
interface GettableInterface
{
    /**
     * @param array<string, mixed>|QueryParametersInterface|null $params
     *
     * @return TDto
     */
    public function get(int $id, array|QueryParametersInterface|null $params = null): object;
}
