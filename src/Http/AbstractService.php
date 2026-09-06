<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Http;

use Generator;
use JOOservices\Client\Request\MultipartPart;
use JOOservices\Client\Request\RequestBuilder;
use JOOservices\Dto\Core\Dto;
use JOOservices\WordPress\Sdk\Contracts\QueryParametersInterface;
use JOOservices\WordPress\Sdk\Contracts\ResponseDecoderInterface;
use JOOservices\WordPress\Sdk\Contracts\Writable\PayloadInterface;
use JOOservices\WordPress\Sdk\Pagination\PaginatedCollection;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Shared request primitives for every service.
 *
 * The template-method core of the SDK: subclasses compose the typed helpers
 * (`getItem`, `getList`, `createItem`, `updateItem`, `deleteAndDecode`,
 * `requestArray`) with their own endpoint and DTO contracts.
 *
 * Requests are built with the `jooservices/client` RequestBuilder — a single
 * builder handles methods, headers, query strings, JSON bodies, and multipart
 * uploads. Client exceptions (timeouts, DNS, connection) propagate untouched
 * from the transport layer; HTTP error statuses are mapped by `ErrorMapper`.
 */
abstract class AbstractService
{
    public function __construct(
        protected readonly ClientInterface $client,
        protected readonly RequestBuilder $requestBuilder,
        protected readonly ResponseDecoderInterface $decoder,
        protected readonly ErrorMapper $errorMapper,
    ) {}

    /**
     * @param array<string, mixed> $options
     */
    protected function request(string $method, string $uri, array $options = []): ResponseInterface
    {
        $response = $this->client->sendRequest($this->buildRequest($method, $uri, $options));

        if ($response->getStatusCode() >= 400) {
            throw $this->errorMapper->map($response);
        }

        return $response;
    }

    /**
     * @template TDto of Dto
     *
     * @param class-string<TDto> $dtoClass
     * @param array<string, mixed> $options
     *
     * @return TDto
     */
    protected function getItem(string $uri, string $dtoClass, array $options = []): object
    {
        return $this->decoder->decodeItem($this->request('GET', $uri, $options), $dtoClass);
    }

    /**
     * @template TDto of Dto
     *
     * @param class-string<TDto> $dtoClass
     * @param array<string, mixed> $options
     *
     * @return PaginatedCollection<TDto>
     */
    protected function getList(string $uri, string $dtoClass, array $options = []): PaginatedCollection
    {
        return $this->decoder->decodeList($this->request('GET', $uri, $options), $dtoClass);
    }

    /**
     * @template TDto of Dto
     *
     * @param class-string<TDto> $dtoClass
     * @param array<string, mixed> $payload
     *
     * @return TDto
     */
    protected function createItem(string $uri, array $payload, string $dtoClass): object
    {
        return $this->decoder->decodeItem(
            $this->request('POST', $uri, ['body' => $payload]),
            $dtoClass,
        );
    }

    /**
     * WordPress accepts POST for updates on its REST resources.
     *
     * @template TDto of Dto
     *
     * @param class-string<TDto> $dtoClass
     * @param array<string, mixed> $payload
     *
     * @return TDto
     */
    protected function updateItem(string $uri, array $payload, string $dtoClass): object
    {
        return $this->decoder->decodeItem(
            $this->request('POST', $uri, ['body' => $payload]),
            $dtoClass,
        );
    }

    /**
     * Deletes an item and decodes the response, unwrapping WordPress
     * force-delete payloads (`{deleted: true, previous: {…}}`).
     *
     * @template TDto of Dto
     *
     * @param class-string<TDto> $dtoClass
     * @param array<string, mixed> $options
     *
     * @return TDto
     */
    protected function deleteAndDecode(string $uri, string $dtoClass, array $options = []): object
    {
        $response = $this->request('DELETE', $uri, $options);

        /** @var array<string, mixed>|null $data */
        $data = json_decode((string) $response->getBody(), true);
        if (! is_array($data)) {
            return $this->decoder->deserialize([], $dtoClass);
        }

        $previous = $data['previous'] ?? null;

        if (is_array($previous)) {
            /** @var array<string, mixed> $previous */
            return $this->decoder->deserialize($previous, $dtoClass);
        }

        return $this->decoder->deserialize($data, $dtoClass);
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    protected function requestArray(string $method, string $uri, array $options = []): array
    {
        $response = $this->request($method, $uri, $options);

        /** @var array<string, mixed>|null $data */
        $data = json_decode((string) $response->getBody(), true);

        return is_array($data) ? $data : [];
    }

    /**
     * @param array<string, mixed>|QueryParametersInterface|null $params
     *
     * @return array<string, mixed>
     */
    protected function normalizeQueryParameters(array|QueryParametersInterface|null $params): array
    {
        if ($params instanceof QueryParametersInterface) {
            return $params->toQuery();
        }

        return $params ?? [];
    }

    /**
     * @param array<string, mixed>|PayloadInterface $payload
     *
     * @return array<string, mixed>
     */
    protected function payloadArray(array|PayloadInterface $payload): array
    {
        return $payload instanceof PayloadInterface ? $payload->toPayload() : $payload;
    }

    /**
     * Streams every page of a collection lazily.
     *
     * @template TDto of Dto
     *
     * @param class-string<TDto> $dtoClass
     * @param array<string, mixed> $params
     *
     * @return Generator<int, TDto>
     */
    protected function cursorItems(string $uri, string $dtoClass, array $params): Generator
    {
        $page = 1;
        if (isset($params['page']) && is_numeric($params['page'])) {
            $page = max(1, (int) $params['page']);
        }

        while (true) {
            $collection = $this->getList($uri, $dtoClass, [
                'query' => [...$params, 'page' => $page],
            ]);

            foreach ($collection as $item) {
                yield $item;
            }

            if ($collection->totalPages <= $page) {
                return;
            }

            $page++;
        }
    }

    /**
     * @template TDto of Dto
     *
     * @param class-string<TDto> $dtoClass
     * @param array<string, mixed> $params
     *
     * @return list<TDto>
     */
    protected function collectAll(string $uri, string $dtoClass, array $params): array
    {
        return iterator_to_array($this->cursorItems($uri, $dtoClass, $params), false);
    }

    /**
     * @template TDto of Dto
     *
     * @param class-string<TDto> $dtoClass
     * @param callable(TDto): mixed $callback return false to stop early
     * @param array<string, mixed> $params
     */
    protected function eachItem(string $uri, string $dtoClass, callable $callback, array $params): void
    {
        foreach ($this->cursorItems($uri, $dtoClass, $params) as $item) {
            if ($callback($item) === false) {
                return;
            }
        }
    }

    /**
     * @param array<string, mixed> $options
     */
    private function buildRequest(string $method, string $uri, array $options): RequestInterface
    {
        $builder = $this->requestBuilder->method($method, $uri);

        /** @var array<string, string> $headers */
        $headers = $options['headers'] ?? [];
        foreach ($headers as $name => $value) {
            $builder = $builder->withHeader($name, $value);
        }

        if (isset($options['query']) && is_array($options['query'])) {
            /** @var array<string, array<bool|float|int|string>|bool|float|int|string|null> $query */
            $query = $options['query'];
            $builder = $builder->withQuery($query);
        }

        if (isset($options['body']) && is_array($options['body'])) {
            $builder = $builder->withJson($options['body']);
        }

        if (isset($options['multipart']) && is_array($options['multipart'])) {
            /** @var list<MultipartPart|array<string, mixed>> $parts */
            $parts = $options['multipart'];
            $builder = $builder->withMultipart($parts);
        }

        return $builder->build()->toPsr();
    }
}
