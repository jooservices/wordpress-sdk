<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Services;

use JOOservices\WordPress\Sdk\Data\Page;
use JOOservices\WordPress\Sdk\Endpoints\Endpoint;

/**
 * @extends AbstractCrudService<Page>
 */
final class PagesService extends AbstractCrudService
{
    public function revisions(int $id): RevisionResourceService
    {
        return new RevisionResourceService(
            new RevisionsService($this->client, $this->requestBuilder, $this->decoder, $this->errorMapper),
            Endpoint::PAGES->withChild($id, 'revisions'),
        );
    }

    public function autosaves(int $id): AutosaveResourceService
    {
        return new AutosaveResourceService(
            new AutosavesService($this->client, $this->requestBuilder, $this->decoder, $this->errorMapper),
            Endpoint::PAGES->withChild($id, 'autosaves'),
        );
    }

    protected function dtoClass(): string
    {
        return Page::class;
    }

    protected function listPath(): string
    {
        return Endpoint::PAGES->path();
    }
}
