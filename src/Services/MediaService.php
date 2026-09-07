<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Services;

use InvalidArgumentException;
use JOOservices\Client\Request\MultipartPart;
use JOOservices\WordPress\Sdk\Contracts\Writable\PayloadInterface;
use JOOservices\WordPress\Sdk\Data\Media;
use JOOservices\WordPress\Sdk\Endpoints\Endpoint;

/**
 * @extends AbstractCrudService<Media>
 */
final class MediaService extends AbstractCrudService
{
    /**
     * JSON POST is not a supported WordPress media create. Use {@see upload()}.
     *
     * @param array<string, mixed>|PayloadInterface $payload
     */
    #[\Override]
    public function create(array|PayloadInterface $payload): object
    {
        unset($payload);

        throw new InvalidArgumentException(
            'Creating media requires a multipart upload. Use MediaService::upload().',
        );
    }

    /** @return array<string, mixed> */
    public function postProcess(int $id, string $action): array
    {
        return $this->requestArray('POST', Endpoint::MEDIA->withChild($id, 'post-process'), [
            'body' => ['action' => $action],
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function edit(int $id, array $payload): array
    {
        return $this->requestArray('POST', Endpoint::MEDIA->withChild($id, 'edit'), ['body' => $payload]);
    }

    /**
     * Uploads a file as a new media item (multipart/form-data).
     *
     * @param array<string, mixed> $attributes scalar payload fields sent
     *                                        alongside the file (title,
     *                                        caption, alt_text, status, …)
     */
    public function upload(string $filePath, array $attributes = []): Media
    {
        if (! is_file($filePath) || ! is_readable($filePath)) {
            throw new InvalidArgumentException(sprintf('File not found or not readable: %s', $filePath));
        }

        $stream = fopen($filePath, 'rb');

        if ($stream === false) {
            throw new InvalidArgumentException(sprintf('File could not be opened: %s', $filePath));
        }

        $parts = [new MultipartPart('file', $stream, filename: basename($filePath))];

        foreach ($attributes as $key => $value) {
            if (is_scalar($value)) {
                $parts[] = new MultipartPart((string) $key, (string) $value);
            }
        }

        try {
            $response = $this->request('POST', Endpoint::MEDIA->path(), ['multipart' => $parts]);

            /** @var Media */
            return $this->decoder->decodeItem($response, Media::class);
        } finally {
            fclose($stream);
        }
    }

    protected function dtoClass(): string
    {
        return Media::class;
    }

    protected function listPath(): string
    {
        return Endpoint::MEDIA->path();
    }
}
