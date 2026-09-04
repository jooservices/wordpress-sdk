<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Services;

use InvalidArgumentException;
use JOOservices\Client\Request\MultipartPart;
use JOOservices\WordPress\Sdk\Endpoints\Endpoint;
use JsonException;

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
     * Creates a font face from a JSON/body payload (no binary file).
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function createFace(int $familyId, array $payload): array
    {
        return $this->postRaw($this->facesPath($familyId), $payload);
    }

    /**
     * Uploads a font file as a new face under a family (multipart/form-data).
     *
     * WordPress expects a binary part named `file` and a required
     * `font_face_settings` JSON string whose `src` references that part.
     *
     * @param array<string, mixed> $settings theme.json font-face fields
     *                                       (`fontFamily`, `fontWeight`, `fontStyle`, …).
     *                                       Defaults `src` to `['file']` when omitted.
     * @return array<string, mixed>
     */
    public function uploadFace(int $familyId, string $filePath, array $settings = []): array
    {
        if (! is_file($filePath) || ! is_readable($filePath)) {
            throw new InvalidArgumentException(sprintf('File not found or not readable: %s', $filePath));
        }

        $stream = fopen($filePath, 'rb');
        if ($stream === false) {
            throw new InvalidArgumentException(sprintf('File could not be opened: %s', $filePath));
        }

        $encoded = $this->encodeFontFaceSettings($settings);

        $parts = [
            new MultipartPart('file', $stream, filename: basename($filePath)),
            new MultipartPart('font_face_settings', $encoded),
        ];

        try {
            return $this->requestArray('POST', $this->facesPath($familyId), ['multipart' => $parts]);
        } finally {
            fclose($stream);
        }
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
        return $this->getRaw(Endpoint::FONT_COLLECTIONS->withKey($slug));
    }

    /**
     * @param array<string, mixed> $settings
     */
    private function encodeFontFaceSettings(array $settings): string
    {
        if (! array_key_exists('src', $settings)) {
            $settings['src'] = ['file'];
        }

        try {
            return json_encode($settings, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException('font_face_settings could not be encoded as JSON.', 0, $exception);
        }
    }

    private function facesPath(int $familyId): string
    {
        return Endpoint::FONT_FAMILIES->withChild($familyId, 'font-faces');
    }
}
