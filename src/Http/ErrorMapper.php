<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Http;

use JOOservices\WordPress\Sdk\Exceptions\BadRequestException;
use JOOservices\WordPress\Sdk\Exceptions\ConflictException;
use JOOservices\WordPress\Sdk\Exceptions\ForbiddenException;
use JOOservices\WordPress\Sdk\Exceptions\NotFoundException;
use JOOservices\WordPress\Sdk\Exceptions\RateLimitException;
use JOOservices\WordPress\Sdk\Exceptions\ServerException;
use JOOservices\WordPress\Sdk\Exceptions\UnauthorizedException;
use JOOservices\WordPress\Sdk\Exceptions\ValidationException;
use JOOservices\WordPress\Sdk\Exceptions\WordPressApiException;
use Psr\Http\Message\ResponseInterface;

/**
 * Maps a WordPress REST response to the typed exception hierarchy.
 */
final class ErrorMapper
{
    public function map(ResponseInterface $response): WordPressApiException
    {
        $statusCode = $response->getStatusCode();
        $data = $this->decodeBody($response);

        $message = isset($data['message']) && is_string($data['message'])
            ? $data['message']
            : $response->getReasonPhrase();

        return match (true) {
            $statusCode === 400 => $this->handleBadRequest($data, $message),
            $statusCode === 401 => new UnauthorizedException($message, 401, $data),
            $statusCode === 403 => new ForbiddenException($message, 403, $data),
            $statusCode === 404 => new NotFoundException($message, 404, $data),
            $statusCode === 409 => new ConflictException($message, 409, $data),
            $statusCode === 422 => new ValidationException(
                $this->validationParams($data),
                $message,
                422,
                data: $data !== [] ? $data : null,
            ),
            $statusCode === 429 => new RateLimitException($message, 429, $data),
            $statusCode >= 500 => new ServerException($message, $statusCode, $data),
            default => new WordPressApiException($message, $statusCode, $data),
        };
    }

    /**
     * @param array<string, mixed> $data
     */
    private function handleBadRequest(array $data, string $message): WordPressApiException
    {
        if (($data['code'] ?? null) === 'rest_invalid_param') {
            return new ValidationException(
                $this->validationParams($data),
                $message,
                400,
                data: $data,
            );
        }

        return new BadRequestException($message, 400, $data);
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function validationParams(array $data): array
    {
        $payload = $data['data'] ?? null;
        if (! is_array($payload)) {
            return [];
        }

        $params = $payload['params'] ?? [];

        /** @var array<string, mixed> $params */
        return is_array($params) ? $params : [];
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeBody(ResponseInterface $response): array
    {
        /** @var array<string, mixed>|null $decoded */
        $decoded = json_decode((string) $response->getBody(), true);

        return is_array($decoded) ? $decoded : [];
    }
}
