<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Http;

use JOOservices\Client\Testing\TestResponse;
use JOOservices\WordPress\Sdk\Exceptions\BadRequestException;
use JOOservices\WordPress\Sdk\Exceptions\ConflictException;
use JOOservices\WordPress\Sdk\Exceptions\ForbiddenException;
use JOOservices\WordPress\Sdk\Exceptions\NotFoundException;
use JOOservices\WordPress\Sdk\Exceptions\RateLimitException;
use JOOservices\WordPress\Sdk\Exceptions\ServerException;
use JOOservices\WordPress\Sdk\Exceptions\UnauthorizedException;
use JOOservices\WordPress\Sdk\Exceptions\ValidationException;
use JOOservices\WordPress\Sdk\Exceptions\WordPressApiException;
use JOOservices\WordPress\Sdk\Http\ErrorMapper;
use JOOservices\WordPress\Sdk\Tests\TestCase;

final class ErrorMapperTest extends TestCase
{
    private ErrorMapper $mapper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mapper = new ErrorMapper();
    }

    public function testMapsStatusCodes(): void
    {
        self::assertInstanceOf(BadRequestException::class, $this->map(400));
        self::assertInstanceOf(UnauthorizedException::class, $this->map(401));
        self::assertInstanceOf(ForbiddenException::class, $this->map(403));
        self::assertInstanceOf(NotFoundException::class, $this->map(404));
        self::assertInstanceOf(ConflictException::class, $this->map(409));
        self::assertInstanceOf(ValidationException::class, $this->map(422));
        self::assertInstanceOf(RateLimitException::class, $this->map(429));
        self::assertInstanceOf(ServerException::class, $this->map(500));
        self::assertInstanceOf(ServerException::class, $this->map(502));
        self::assertInstanceOf(ServerException::class, $this->map(503));
        self::assertInstanceOf(ServerException::class, $this->map(504));
    }

    public function testMapsOtherErrorsToBaseException(): void
    {
        self::assertNotInstanceOf(ServerException::class, $this->map(418));
    }

    public function testUsesWordPressMessage(): void
    {
        $exception = $this->mapper->map(TestResponse::make(404, [], json_encode([
            'code' => 'rest_post_invalid_id',
            'message' => 'Invalid post ID.',
            'data' => ['status' => 404],
        ], JSON_THROW_ON_ERROR)));

        self::assertSame('Invalid post ID.', $exception->getMessage());
        self::assertSame('rest_post_invalid_id', $exception->data['code'] ?? null);
    }

    public function testFallsBackToReasonPhrase(): void
    {
        $exception = $this->map(404);

        self::assertNotSame('', $exception->getMessage());
    }

    public function testInvalidParamBadRequestBecomesValidationException(): void
    {
        $exception = $this->mapper->map(TestResponse::make(400, [], json_encode([
            'code' => 'rest_invalid_param',
            'message' => 'Invalid parameter(s)',
            'data' => [
                'status' => 400,
                'params' => ['title' => 'Cannot be empty.'],
            ],
        ], JSON_THROW_ON_ERROR)));

        self::assertInstanceOf(ValidationException::class, $exception);
        self::assertSame(['title' => 'Cannot be empty.'], $exception->params);
        self::assertSame(400, $exception->getCode());
        self::assertSame('rest_invalid_param', $exception->data['code'] ?? null);
        self::assertSame(
            ['status' => 400, 'params' => ['title' => 'Cannot be empty.']],
            $exception->data['data'] ?? null,
        );
    }

    public function testValidationExceptionCarriesParams(): void
    {
        $payload = [
            'code' => 'rest_invalid_param',
            'message' => 'Invalid parameter(s)',
            'data' => [
                'status' => 422,
                'params' => ['slug' => 'Invalid slug.'],
                'details' => ['slug' => ['code' => 'rest_invalid_param']],
            ],
        ];
        $exception = $this->mapper->map(TestResponse::make(422, [], json_encode($payload, JSON_THROW_ON_ERROR)));

        self::assertInstanceOf(ValidationException::class, $exception);
        self::assertSame(['slug' => 'Invalid slug.'], $exception->params);
        self::assertSame($payload, $exception->data);
    }

    public function testValidationWithoutParamsMap(): void
    {
        $exception = $this->mapper->map(TestResponse::make(422, [], json_encode([
            'code' => 'rest_invalid_param',
            'message' => 'Invalid parameter(s)',
            'data' => ['status' => 422],
        ], JSON_THROW_ON_ERROR)));

        self::assertInstanceOf(ValidationException::class, $exception);
        self::assertSame([], $exception->params);
    }

    public function testValidationWithEmptyBodyUsesDefaultPayload(): void
    {
        $exception = $this->mapper->map(TestResponse::make(422, [], '{}'));

        self::assertInstanceOf(ValidationException::class, $exception);
        self::assertSame([], $exception->params);
        self::assertSame('rest_invalid_param', $exception->data['code'] ?? null);
        self::assertSame(
            ['status' => 422, 'params' => []],
            $exception->data['data'] ?? null,
        );
    }

    public function testNonJsonBodyStillMapsStatus(): void
    {
        $exception = $this->mapper->map(TestResponse::make(500, [], '<html>oops</html>'));

        self::assertInstanceOf(ServerException::class, $exception);
        self::assertSame([], $exception->data);
    }

    private function map(int $status): WordPressApiException
    {
        return $this->mapper->map(TestResponse::make($status, [], '{}'));
    }
}
