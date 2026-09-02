<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Exceptions;

use JOOservices\WordPress\Sdk\Exceptions\BadRequestException;
use JOOservices\WordPress\Sdk\Exceptions\ForbiddenException;
use JOOservices\WordPress\Sdk\Exceptions\NotFoundException;
use JOOservices\WordPress\Sdk\Exceptions\RateLimitException;
use JOOservices\WordPress\Sdk\Exceptions\ServerException;
use JOOservices\WordPress\Sdk\Exceptions\UnauthorizedException;
use JOOservices\WordPress\Sdk\Exceptions\ValidationException;
use JOOservices\WordPress\Sdk\Exceptions\WordPressApiException;
use JOOservices\WordPress\Sdk\Tests\TestCase;
use LogicException;

final class ExceptionsTest extends TestCase
{
    public function testBaseExceptionCarriesRawPayload(): void
    {
        $exception = new WordPressApiException('Something failed', 418, ['code' => 'oops', 'data' => []]);

        self::assertSame('Something failed', $exception->getMessage());
        self::assertSame(418, $exception->getCode());
        self::assertSame(['code' => 'oops', 'data' => []], $exception->data);
    }

    public function testSubclassesShareTheBaseException(): void
    {
        $cases = [
            BadRequestException::class => 400,
            UnauthorizedException::class => 401,
            ForbiddenException::class => 403,
            NotFoundException::class => 404,
            RateLimitException::class => 429,
            ServerException::class => 500,
        ];

        foreach ($cases as $class => $code) {
            $exception = new $class('x', $code);

            self::assertSame($code, $exception->getCode());
        }
    }

    public function testValidationExceptionCarriesParamsMap(): void
    {
        $exception = new ValidationException(['title' => 'Cannot be empty.']);

        self::assertSame(['title' => 'Cannot be empty.'], $exception->params);
        self::assertSame(422, $exception->getCode());
        self::assertSame('rest_invalid_param', $exception->data['code'] ?? null);
    }

    public function testToArrayStructure(): void
    {
        $exception = new WordPressApiException('Not found', 404, [
            'code' => 'rest_post_invalid_id',
            'message' => 'Not found',
            'data' => ['status' => 404],
        ]);

        $array = $exception->toArray();

        self::assertSame(WordPressApiException::class, $array['type']);
        self::assertSame('Not found', $array['message']);
        self::assertSame(404, $array['status_code']);
        self::assertSame('rest_post_invalid_id', $array['wordpress_code']);
        self::assertSame(['status' => 404], $array['wordpress_data']);
        self::assertSame(['code' => 'rest_post_invalid_id', 'message' => 'Not found', 'data' => ['status' => 404]], $array['response']);
        self::assertNull($array['previous']);
    }

    public function testToArrayRedactsCredentials(): void
    {
        $exception = new WordPressApiException('Denied', 403, [
            'code' => 'rest_cookie_invalid_nonce',
            'message' => 'Denied',
            'data' => [
                'authorization' => 'Basic YWRtaW46cGFzcw==',
                'password' => 'secret',
                'application_password' => 'xxxx xxxx xxxx xxxx',
                'safe' => 'keep me',
                'nested' => ['token' => 'abc', 'keep' => 'value'],
            ],
        ]);

        /** @var array<string, mixed> $data */
        $data = $exception->toArray()['wordpress_data'] ?? [];
        /** @var array<string, mixed> $nested */
        $nested = $data['nested'] ?? [];

        self::assertSame('(redacted)', $data['authorization'] ?? null);
        self::assertSame('(redacted)', $data['password'] ?? null);
        self::assertSame('(redacted)', $data['application_password'] ?? null);
        self::assertSame('keep me', $data['safe'] ?? null);
        self::assertSame('(redacted)', $nested['token'] ?? null);
        self::assertSame('value', $nested['keep'] ?? null);
    }

    public function testToArrayRedactsBasicAndBearerValues(): void
    {
        $exception = new WordPressApiException('x', 401, [
            'message' => 'x',
            'data' => ['header' => 'Basic dXNlcjpwYXNz'],
        ]);

        /** @var array<string, mixed> $data */
        $data = $exception->toArray()['wordpress_data'] ?? [];
        self::assertSame('(redacted)', $data['header'] ?? null);

        $exception = new WordPressApiException('x', 401, [
            'message' => 'x',
            'data' => ['header' => 'Bearer eyJhbGciOiJIUzI1NiJ9'],
        ]);

        /** @var array<string, mixed> $data */
        $data = $exception->toArray()['wordpress_data'] ?? [];
        self::assertSame('(redacted)', $data['header'] ?? null);
    }

    public function testToArrayRedactsAppPasswordShapedValues(): void
    {
        $exception = new WordPressApiException('x', 400, [
            'message' => 'x',
            'data' => ['value' => 'abcd efgh ijkl mnop'],
        ]);

        /** @var array<string, mixed> $data */
        $data = $exception->toArray()['wordpress_data'] ?? [];
        self::assertSame('(redacted)', $data['value'] ?? null);
    }

    public function testToArrayNeverReturnsSecretsBeyondDepthLimit(): void
    {
        $value = 'Basic leaked-credential';
        for ($depth = 0; $depth < 9; $depth++) {
            $value = ['level' => $value];
        }

        $exception = new WordPressApiException('x', 500, ['data' => $value]);

        $redacted = $exception->toArray()['wordpress_data'];
        for ($depth = 0; $depth < 9; $depth++) {
            self::assertIsArray($redacted);
            $redacted = $redacted['level'];
        }

        self::assertSame('(depth limit)', $redacted);
    }

    public function testToArrayRedactsAuthorizationSchemesCaseInsensitively(): void
    {
        $exception = new WordPressApiException('x', 401, [
            'data' => ['header' => 'bearer token-value'],
        ]);

        /** @var array<string, mixed> $data */
        $data = $exception->toArray()['wordpress_data'] ?? [];

        self::assertSame('(redacted)', $data['header'] ?? null);
    }

    public function testToArrayWithoutPayload(): void
    {
        $exception = new WordPressApiException('plain');

        $array = $exception->toArray();

        self::assertNull($array['status_code']);
        self::assertNull($array['wordpress_code']);
        self::assertNull($array['wordpress_data']);
        self::assertNull($array['response']);
    }

    public function testToArrayReportsPrevious(): void
    {
        $exception = new WordPressApiException('outer', 500, null, new LogicException('inner'));

        self::assertSame(LogicException::class, $exception->toArray()['previous']);
    }
}
