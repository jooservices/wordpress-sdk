<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Services;

use JOOservices\Client\Request\RequestBuilder;
use JOOservices\WordPress\Sdk\Contracts\QueryParametersInterface;
use JOOservices\WordPress\Sdk\Contracts\ResponseDecoderInterface;
use JOOservices\WordPress\Sdk\Data\Term;
use JOOservices\WordPress\Sdk\Http\ErrorMapper;
use JOOservices\WordPress\Sdk\Pagination\PaginatedCollection;
use InvalidArgumentException;
use Psr\Http\Client\ClientInterface;

/**
 * Typed CRUD against a taxonomy REST collection (categories, tags, or a
 * `show_in_rest` custom taxonomy).
 *
 * @extends AbstractCrudService<Term>
 */
final class TermsService extends AbstractCrudService
{
    public function __construct(
        ClientInterface $client,
        RequestBuilder $requestBuilder,
        ResponseDecoderInterface $decoder,
        ErrorMapper $errorMapper,
        private readonly string $path,
        private readonly bool $hierarchical = false,
    ) {
        parent::__construct($client, $requestBuilder, $decoder, $errorMapper);
    }

    /**
     * Hierarchical taxonomies do not support the REST `offset` parameter and
     * paginate via `page`/`per_page`; `offset` is dropped for them.
     *
     * @param array<string, mixed>|QueryParametersInterface|null $params
     *
     * @return PaginatedCollection<Term>
     */
    #[\Override]
    public function list(array|QueryParametersInterface|null $params = null): PaginatedCollection
    {
        $query = $this->normalizeQueryParameters($params);
        if ($this->hierarchical) {
            unset($query['offset']);
        }

        return $this->fetchPage($query);
    }

    /**
     * WordPress only deletes terms with `force=true`; an explicit `false`
     * is rejected before any request is sent.
     *
     * @return Term
     */
    #[\Override]
    public function delete(int $id, bool $force = true): object
    {
        if (! $force) {
            throw new InvalidArgumentException('Term deletion requires force=true.');
        }

        return parent::delete($id, true);
    }

    protected function dtoClass(): string
    {
        return Term::class;
    }

    protected function listPath(): string
    {
        return $this->path;
    }
}
