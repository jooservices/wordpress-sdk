<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Services;

use JOOservices\Client\Request\RequestBuilder;
use JOOservices\WordPress\Sdk\Contracts\ResponseDecoderInterface;
use JOOservices\WordPress\Sdk\Data\Term;
use JOOservices\WordPress\Sdk\Http\ErrorMapper;
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
    ) {
        parent::__construct($client, $requestBuilder, $decoder, $errorMapper);
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
