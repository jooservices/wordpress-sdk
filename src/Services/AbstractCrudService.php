<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Services;

use JOOservices\Dto\Core\Dto;
use JOOservices\WordPress\Sdk\Contracts\QueryParametersInterface;
use JOOservices\WordPress\Sdk\Contracts\Readable\GettableInterface;
use JOOservices\WordPress\Sdk\Contracts\Writable\CreatableInterface;
use JOOservices\WordPress\Sdk\Contracts\Writable\DeletableInterface;
use JOOservices\WordPress\Sdk\Contracts\Writable\UpdatableInterface;

/**
 * Base for services with typed CRUD over an integer-keyed resource.
 *
 * @template TDto of Dto
 *
 * @extends AbstractCollectionService<TDto>
 * @implements GettableInterface<TDto>
 * @implements CreatableInterface<TDto>
 * @implements UpdatableInterface<TDto>
 * @implements DeletableInterface<TDto>
 */
abstract class AbstractCrudService extends AbstractCollectionService implements
    GettableInterface,
    CreatableInterface,
    UpdatableInterface,
    DeletableInterface
{
    /**
     * @param array<string, mixed>|QueryParametersInterface|null $params
     *
     * @return TDto
     */
    public function get(int $id, array|QueryParametersInterface|null $params = null): object
    {
        return $this->getItem(
            $this->itemPath($id),
            $this->dtoClass(),
            ['query' => $this->normalizeQueryParameters($params)],
        );
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return TDto
     */
    public function create(array $payload): object
    {
        return $this->createItem($this->listPath(), $payload, $this->dtoClass());
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return TDto
     */
    public function update(int $id, array $payload): object
    {
        return $this->updateItem($this->itemPath($id), $payload, $this->dtoClass());
    }

    /**
     * @return TDto
     */
    public function delete(int $id, bool $force = false): object
    {
        return $this->deleteAndDecode(
            $this->itemPath($id),
            $this->dtoClass(),
            $force ? ['query' => ['force' => 'true']] : [],
        );
    }

    protected function itemPath(int $id): string
    {
        return $this->listPath() . '/' . $id;
    }
}
