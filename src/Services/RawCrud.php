<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Services;

/**
 * Raw-array CRUD over a base path. Handles both integer and string resource
 * ids (posts vs templates/widgets) through one implementation.
 */
trait RawCrud
{
    /**
     * @param array<string, mixed> $query
     *
     * @return array<string, mixed>
     */
    public function list(array $query = []): array
    {
        return $this->getRaw($this->basePath(), $query);
    }

    /**
     * @return array<string, mixed>
     */
    public function get(int|string $id): array
    {
        return $this->getRaw($this->basePath() . '/' . $this->segment($id));
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function create(array $payload): array
    {
        return $this->postRaw($this->basePath(), $payload);
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function update(int|string $id, array $payload): array
    {
        return $this->postRaw($this->basePath() . '/' . $this->segment($id), $payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function delete(int|string $id, bool $force = false): array
    {
        return $this->deleteRaw(
            $this->basePath() . '/' . $this->segment($id),
            $force ? ['force' => 'true'] : [],
        );
    }

    abstract protected function basePath(): string;
}
