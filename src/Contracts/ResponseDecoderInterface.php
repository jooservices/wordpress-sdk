<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Contracts;

use JOOservices\Dto\Core\Dto;
use JOOservices\WordPress\Sdk\Pagination\PaginatedCollection;
use Psr\Http\Message\ResponseInterface;

/**
 * Decodes WordPress REST responses into DTOs.
 */
interface ResponseDecoderInterface
{
    /**
     * @template T of Dto
     *
     * @param class-string<T> $dtoClass
     *
     * @return T
     */
    public function decodeItem(ResponseInterface $response, string $dtoClass): object;

    /**
     * @template T of Dto
     *
     * @param class-string<T> $dtoClass
     *
     * @return PaginatedCollection<T>
     */
    public function decodeList(ResponseInterface $response, string $dtoClass): PaginatedCollection;

    /**
     * @template T of Dto
     *
     * @param class-string<T> $dtoClass
     *
     * @return list<T>
     */
    public function decodeArray(ResponseInterface $response, string $dtoClass): array;

    /**
     * @template T of Dto
     *
     * @param class-string<T> $dtoClass
     * @param array<string, mixed> $data
     *
     * @return T
     */
    public function deserialize(array $data, string $dtoClass): object;
}
