<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Http;

use JOOservices\Dto\Core\Dto;
use JOOservices\Dto\Exceptions\DtoException;
use JOOservices\WordPress\Sdk\Contracts\ResponseDecoderInterface;
use JOOservices\WordPress\Sdk\Exceptions\ServerException;
use JOOservices\WordPress\Sdk\Pagination\PaginatedCollection;
use JsonException;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Decodes WordPress REST responses into DTOs through the `jooservices/dto`
 * hydration engine.
 *
 * - `decodeItem` / `deserialize`: single DTO.
 * - `decodeList`: items plus `X-WP-Total` / `X-WP-TotalPages` into a
 *   `PaginatedCollection`. List-shaped and assoc-shaped payloads (types,
 *   statuses, taxonomies keyed by slug) are both supported.
 * - `decodeArray`: non-paginated list of DTOs.
 *
 * HTML responses (WordPress error pages, maintenance screens) are rejected
 * with a `ServerException` instead of a confusing serialization error.
 */
final readonly class ResponseDecoder implements ResponseDecoderInterface
{
    public function __construct(
        private readonly ?LoggerInterface $logger = null,
    ) {}

    /**
     * @template T of Dto
     *
     * @param class-string<T> $dtoClass
     *
     * @return T
     */
    public function decodeItem(ResponseInterface $response, string $dtoClass): object
    {
        return $this->decodeBody($response, $dtoClass);
    }

    /**
     * @template T of Dto
     *
     * @param class-string<T> $dtoClass
     *
     * @return PaginatedCollection<T>
     */
    public function decodeList(ResponseInterface $response, string $dtoClass): PaginatedCollection
    {
        $body = (string) $response->getBody();
        $this->assertJsonBody($body);

        $decoded = $this->decodeJson($body);

        $items = array_is_list($decoded)
            ? $this->hydrateList($decoded, $dtoClass)
            : $this->hydrateMap($decoded, $dtoClass);

        $total = $this->headerInt($response, 'X-WP-Total', count($items));
        $totalPages = $this->headerInt($response, 'X-WP-TotalPages', $total > 0 ? 1 : 0);

        return new PaginatedCollection($items, $total, $totalPages);
    }

    /**
     * @template T of Dto
     *
     * @param class-string<T> $dtoClass
     *
     * @return list<T>
     */
    public function decodeArray(ResponseInterface $response, string $dtoClass): array
    {
        $body = (string) $response->getBody();
        $this->assertJsonBody($body);

        $decoded = $this->decodeJson($body);

        if (! array_is_list($decoded)) {
            throw $this->decodeFailure($body, null);
        }

        return $this->hydrateList($decoded, $dtoClass);
    }

    /**
     * @template T of Dto
     *
     * @param class-string<T> $dtoClass
     * @param array<string, mixed> $data
     *
     * @return T
     */
    public function deserialize(array $data, string $dtoClass): object
    {
        try {
            return $dtoClass::from($data);
        } catch (DtoException $exception) {
            throw $this->deserializeFailure($dtoClass, $exception);
        }
    }

    /**
     * @template T of Dto
     *
     * @param class-string<T> $dtoClass
     *
     * @return T
     */
    private function decodeBody(ResponseInterface $response, string $dtoClass): object
    {
        $body = (string) $response->getBody();
        $this->assertJsonBody($body);

        try {
            /** @var T $item */
            $item = $dtoClass::from($body);

            return $item;
        } catch (DtoException $exception) {
            throw $this->decodeFailure($body, $exception);
        }
    }

    /**
     * @template T of Dto
     *
     * @param class-string<T> $dtoClass
     * @param array<mixed> $items
     *
     * @return list<T>
     */
    private function hydrateList(array $items, string $dtoClass): array
    {
        try {
            $collection = $dtoClass::collection($items);

            /** @var list<T> $hydrated */
            $hydrated = iterator_to_array($collection, false);

            return $hydrated;
        } catch (DtoException $exception) {
            throw $this->deserializeFailure($dtoClass, $exception);
        }
    }

    /**
     * Hydrates an assoc-shaped payload (keyed by slug) into a list of DTOs.
     *
     * @template T of Dto
     *
     * @param class-string<T> $dtoClass
     * @param array<mixed> $items
     *
     * @return list<T>
     */
    private function hydrateMap(array $items, string $dtoClass): array
    {
        $hydrated = [];
        foreach ($items as $value) {
            if (is_array($value)) {
                /** @var array<string, mixed> $value */
                $hydrated[] = $this->deserialize($value, $dtoClass);
            }
        }

        /** @var list<T> $hydrated */
        return $hydrated;
    }

    /**
     * @return array<mixed>
     */
    private function decodeJson(string $body): array
    {
        try {
            $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw $this->decodeFailure($body, $exception);
        }

        if (! is_array($decoded)) {
            throw $this->decodeFailure($body, null);
        }

        return $decoded;
    }

    private function headerInt(ResponseInterface $response, string $name, int $default): int
    {
        $value = $response->getHeaderLine($name);

        return $value === '' ? $default : max(0, (int) $value);
    }

    private function assertJsonBody(string $body): void
    {
        $trimmed = ltrim($body);

        if (str_starts_with($trimmed, '<!DOCTYPE html>') || str_starts_with($trimmed, '<html')) {
            throw new ServerException(
                'WordPress returned an HTML page instead of a JSON response. '
                . 'Check that the REST API is enabled and the URL resolves to the API root.',
            );
        }
    }

    private function decodeFailure(string $body, ?Throwable $previous): ServerException
    {
        $this->log('warning', 'Response decoding failed.', [
            'body_length' => strlen($body),
            'body_sha256' => hash('sha256', $body),
        ]);

        return new ServerException(
            'WordPress returned a response that could not be decoded as JSON.',
            0,
            null,
            $previous,
        );
    }

    /**
     * @param class-string $dtoClass
     */
    private function deserializeFailure(string $dtoClass, Throwable $exception): ServerException
    {
        $this->log('warning', 'DTO hydration failed.', [
            'dto' => $dtoClass,
            'exception' => $exception::class,
        ]);

        return new ServerException(
            sprintf('The %s DTO could not be hydrated from the WordPress response.', $dtoClass),
            0,
            null,
            $exception,
        );
    }

    /**
     * @param array<string, mixed> $context
     */
    private function log(string $level, string $message, array $context = []): void
    {
        if ($this->logger === null) {
            return;
        }

        $this->logger->log($level, $message, $context);
    }
}
