<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Services;

use JOOservices\WordPress\Sdk\Endpoints\Endpoint;

/** Font families, nested font faces, and installable font collections. */
final class FontsService extends RawEndpointService
{
    /**
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     */
    public function families(array $query = []): array
    {
        return $this->getRaw(Endpoint::FONT_FAMILIES->path(), $query);
    }
    /** @return array<string, mixed> */
    public function family(int $id): array
    {
        return $this->getRaw(Endpoint::FONT_FAMILIES->withId($id));
    }
    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function createFamily(array $payload): array
    {
        return $this->postRaw(Endpoint::FONT_FAMILIES->path(), $payload);
    }
    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function updateFamily(int $id, array $payload): array
    {
        return $this->postRaw(Endpoint::FONT_FAMILIES->withId($id), $payload);
    }
    /** @return array<string, mixed> */
    public function deleteFamily(int $id, bool $force = true): array
    {
        return $this->deleteRaw(Endpoint::FONT_FAMILIES->withId($id), $force ? ['force' => 'true'] : []);
    }
    /** @return array<string, mixed> */
    public function faces(int $familyId): array
    {
        return $this->getRaw($this->facesPath($familyId));
    }
    /** @return array<string, mixed> */
    public function face(int $familyId, int $id): array
    {
        return $this->getRaw($this->facesPath($familyId) . '/' . $id);
    }
    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function createFace(int $familyId, array $payload): array
    {
        return $this->postRaw($this->facesPath($familyId), $payload);
    }
    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function updateFace(int $familyId, int $id, array $payload): array
    {
        return $this->postRaw($this->facesPath($familyId) . '/' . $id, $payload);
    }
    /** @return array<string, mixed> */
    public function deleteFace(int $familyId, int $id, bool $force = true): array
    {
        return $this->deleteRaw($this->facesPath($familyId) . '/' . $id, $force ? ['force' => 'true'] : []);
    }
    /** @return array<string, mixed> */
    public function collections(): array
    {
        return $this->getRaw(Endpoint::FONT_COLLECTIONS->path());
    }
    /** @return array<string, mixed> */
    public function collection(string $slug): array
    {
        return $this->getRaw(Endpoint::FONT_COLLECTIONS->withKey($this->segment($slug)));
    }

    private function facesPath(int $familyId): string
    {
        return Endpoint::FONT_FAMILIES->withId($familyId) . '/font-faces';
    }
}
