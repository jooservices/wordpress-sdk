<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Exceptions;

use JOOservices\Exceptions\Base\AbstractContextAwareException;
use JOOservices\Exceptions\Contracts\JOOExceptionInterface;
use JOOservices\Exceptions\Contracts\ContextRedactorInterface;
use JOOservices\Exceptions\Support\ExceptionContext;
use JOOservices\WordPress\Sdk\Exceptions\BadRequestException;
use JOOservices\WordPress\Sdk\Exceptions\ConflictException;
use JOOservices\WordPress\Sdk\Exceptions\ForbiddenException;
use JOOservices\WordPress\Sdk\Exceptions\NotFoundException;
use JOOservices\WordPress\Sdk\Exceptions\RateLimitException;
use JOOservices\WordPress\Sdk\Exceptions\ServerException;
use JOOservices\WordPress\Sdk\Exceptions\UnauthorizedException;
use JOOservices\WordPress\Sdk\Exceptions\ValidationException;
use JOOservices\WordPress\Sdk\Exceptions\WordPressApiException;
use JOOservices\WordPress\Sdk\Tests\TestCase;
use LogicException;

final class WordPressApiExceptionTest extends TestCase
{
    public function testBaseExceptionCarriesRawPayload(): void
    {
        $message = $this->faker->word();
        $wordPressCode = $this->faker->slug();
        $exception = new WordPressApiException($message, 418, ['code' => $wordPressCode, 'data' => []]);

        self::assertSame($message, $exception->getMessage());
        self::assertSame(418, $exception->getCode());
        self::assertSame(['code' => $wordPressCode, 'data' => []], $exception->data);
        self::assertSame('wordpress.http.apierror', $exception->errorCode());
        self::assertArrayHasKey('error_code', $exception->toLogArray());
        self::assertSame('wordpress.http.apierror', $exception->toLogArray()['error_code']);
        self::assertContains(JOOExceptionInterface::class, class_implements($exception) ?: []);
    }

    public function testSubclassesShareBaseErrorCodes(): void
    {
        $cases = [
            BadRequestException::class => [400, 'wordpress.http.badrequest'],
            UnauthorizedException::class => [401, 'wordpress.http.unauthorized'],
            ForbiddenException::class => [403, 'wordpress.http.forbidden'],
            NotFoundException::class => [404, 'wordpress.http.notfound'],
            ConflictException::class => [409, 'wordpress.http.conflict'],
            RateLimitException::class => [429, 'wordpress.http.ratelimit'],
            ServerException::class => [500, 'wordpress.http.servererror'],
        ];

        foreach ($cases as $class => [$code, $errorCode]) {
            $exception = new $class($this->faker->sentence(), $code);

            self::assertSame($code, $exception->getCode());
            self::assertSame($errorCode, $exception->errorCode());
            self::assertSame($errorCode, $exception->toLogArray()['error_code']);
        }
    }

    public function testValidationExceptionCarriesParamsMap(): void
    {
        $parameterMessage = $this->faker->sentence();
        $exception = new ValidationException(['title' => $parameterMessage]);

        self::assertSame(['title' => $parameterMessage], $exception->params);
        self::assertSame(422, $exception->getCode());
        self::assertSame('rest_invalid_param', $exception->data['code'] ?? null);
        self::assertSame('wordpress.http.validation', $exception->errorCode());
    }

    public function testValidationExceptionKeepsPayload(): void
    {
        $message = $this->faker->sentence();
        $parameterMessage = $this->faker->sentence();
        $payload = [
            'code' => 'rest_invalid_param',
            'message' => $message,
            'data' => [
                'status' => 422,
                'params' => ['title' => $parameterMessage],
                'details' => ['title' => ['code' => 'rest_missing_callback_param']],
            ],
        ];
        $exception = new ValidationException(['title' => $parameterMessage], $message, 422, data: $payload);

        self::assertSame($payload, $exception->data);
        self::assertSame(['title' => $parameterMessage], $exception->params);
    }

    public function testWithContextPreservesPayload(): void
    {
        $requestId = $this->faker->uuid();
        $exception = (new NotFoundException($this->faker->sentence(), 404, ['code' => 'rest_post_invalid_id']))
            ->withContext(['request_id' => $requestId]);

        self::assertSame('rest_post_invalid_id', $exception->data['code'] ?? null);
        self::assertSame($requestId, $exception->getRawContext()['request_id'] ?? null);
        self::assertSame('wordpress.http.notfound', $exception->errorCode());
    }

    public function testToArrayStructure(): void
    {
        $message = $this->faker->word();
        $exception = new WordPressApiException($message, 404, [
            'code' => 'rest_post_invalid_id',
            'message' => $message,
            'data' => ['status' => 404],
        ]);

        $array = $exception->toArray();

        self::assertSame(WordPressApiException::class, $array['type']);
        self::assertSame($message, $array['message']);
        self::assertSame(404, $array['status_code']);
        self::assertSame('rest_post_invalid_id', $array['wordpress_code']);
        self::assertSame(['status' => 404], $array['wordpress_data']);
        self::assertSame(
            ['code' => 'rest_post_invalid_id', 'message' => $message, 'data' => ['status' => 404]],
            $array['response'],
        );
        self::assertNull($array['previous']);
        self::assertSame('wordpress.http.apierror', $array['error_code']);
    }

    public function testToArrayRedactsCredentials(): void
    {
        $safeValue = $this->faker->word();
        $nestedValue = $this->faker->word();
        $exception = new WordPressApiException($this->faker->sentence(), 403, [
            'code' => 'rest_cookie_invalid_nonce',
            'message' => $this->faker->word(),
            'data' => [
                'authorization' => 'Basic YWRtaW46cGFzcw==',
                'password' => 'secret',
                'application_password' => 'xxxx xxxx xxxx xxxx',
                'safe' => $safeValue,
                'nested' => ['token' => 'abc', 'keep' => $nestedValue],
            ],
        ]);

        /** @var array<string, mixed> $data */
        $data = $exception->toArray()['wordpress_data'] ?? [];
        /** @var array<string, mixed> $nested */
        $nested = $data['nested'] ?? [];

        self::assertSame('(redacted)', $data['authorization'] ?? null);
        self::assertSame('(redacted)', $data['password'] ?? null);
        self::assertSame('(redacted)', $data['application_password'] ?? null);
        self::assertSame($safeValue, $data['safe'] ?? null);
        self::assertSame('(redacted)', $nested['token'] ?? null);
        self::assertSame($nestedValue, $nested['keep'] ?? null);
    }

    public function testToArrayRedactsBasicAndBearerValues(): void
    {
        $message = $this->faker->sentence();
        $exception = new WordPressApiException($message, 401, [
            'message' => $message,
            'data' => ['header' => 'Basic dXNlcjpwYXNz'],
        ]);

        /** @var array<string, mixed> $data */
        $data = $exception->toArray()['wordpress_data'] ?? [];
        self::assertSame('(redacted)', $data['header'] ?? null);

        $exception = new WordPressApiException($message, 401, [
            'message' => $message,
            'data' => ['header' => 'Bearer eyJhbGciOiJIUzI1NiJ9'],
        ]);

        /** @var array<string, mixed> $data */
        $data = $exception->toArray()['wordpress_data'] ?? [];
        self::assertSame('(redacted)', $data['header'] ?? null);
    }

    public function testToArrayRedactsAppPasswords(): void
    {
        $message = $this->faker->sentence();
        $exception = new WordPressApiException($message, 400, [
            'message' => $message,
            'data' => ['value' => 'abcd efgh ijkl mnop'],
        ]);

        /** @var array<string, mixed> $data */
        $data = $exception->toArray()['wordpress_data'] ?? [];
        self::assertSame('(redacted)', $data['value'] ?? null);
    }

    public function testToArrayRedactsDeepSecrets(): void
    {
        $value = 'Basic leaked-credential';
        for ($depth = 0; $depth < 9; $depth++) {
            $value = ['level' => $value];
        }

        $exception = new WordPressApiException($this->faker->sentence(), 500, ['data' => $value]);

        $redacted = $exception->toArray()['wordpress_data'];
        for ($depth = 0; $depth < 9; $depth++) {
            self::assertIsArray($redacted);
            $redacted = $redacted['level'];
        }

        self::assertSame('(depth limit)', $redacted);
    }

    public function testToArrayRedactsAuthSchemes(): void
    {
        $exception = new WordPressApiException($this->faker->sentence(), 401, [
            'data' => ['header' => 'bearer token-value'],
        ]);

        /** @var array<string, mixed> $data */
        $data = $exception->toArray()['wordpress_data'] ?? [];

        self::assertSame('(redacted)', $data['header'] ?? null);
    }

    public function testToArrayAndLogRedactBearerMessage(): void
    {
        $credential = $this->faker->lexify(str_repeat('?', 48));
        $exception = new WordPressApiException(
            sprintf('%s Bearer %s', $this->faker->sentence(), $credential),
        );
        $arrayMessage = $exception->toArray()['message'];
        $logMessage = $exception->toLogArray()['message'];

        self::assertIsString($arrayMessage);
        self::assertIsString($logMessage);
        self::assertStringNotContainsString($credential, $arrayMessage);
        self::assertStringNotContainsString($credential, $logMessage);
        self::assertStringContainsString('Bearer (redacted)', $arrayMessage);
        self::assertStringContainsString('Bearer (redacted)', $logMessage);
    }

    public function testStructuredOutputsRedactUrlSecrets(): void
    {
        $url = 'https://user:review-sentinel@example.test/?token=review-sentinel';
        $message = sprintf('Request failed for %s', $url);
        $exception = new WordPressApiException(
            $message,
            502,
            [
                'code' => $url,
                'message' => sprintf('WordPress rejected %s', $url),
                'data' => ['resource' => ['url' => $url]],
            ],
            context: new ExceptionContext([
                'request' => ['url' => $url],
                'description' => sprintf('Request target %s', $url),
            ]),
        );
        $safeUrl = 'https://(redacted)@example.test/?token=(redacted)';
        $structured = $exception->toArray();
        $context = $exception->getContext();
        $log = $exception->toLogArray();
        $structuredMessage = $structured['message'] ?? null;
        $wordpressCode = $structured['wordpress_code'] ?? null;
        $response = $structured['response'] ?? null;
        $wordpressData = $structured['wordpress_data'] ?? null;
        $requestContext = $context['request'] ?? null;
        $logContext = $log['context'] ?? null;

        self::assertSame($message, $exception->getMessage());
        self::assertIsString($structuredMessage);
        self::assertIsString($wordpressCode);
        self::assertIsArray($response);
        self::assertIsArray($response['data'] ?? null);
        self::assertIsArray($response['data']['resource'] ?? null);
        self::assertIsArray($wordpressData);
        self::assertIsArray($wordpressData['resource'] ?? null);
        self::assertIsArray($requestContext);
        self::assertIsArray($logContext);
        self::assertIsArray($logContext['request'] ?? null);
        self::assertIsString($context['description'] ?? null);
        self::assertIsString($log['message'] ?? null);
        self::assertIsString($logContext['description'] ?? null);
        self::assertIsString($response['code'] ?? null);
        self::assertIsString($response['data']['resource']['url'] ?? null);
        self::assertIsString($wordpressData['resource']['url'] ?? null);
        self::assertIsString($requestContext['url'] ?? null);
        self::assertIsString($logContext['request']['url'] ?? null);
        self::assertStringContainsString($safeUrl, $structuredMessage);
        self::assertSame($safeUrl, $wordpressCode);
        self::assertSame($safeUrl, $response['code']);
        self::assertSame($safeUrl, $response['data']['resource']['url']);
        self::assertSame($safeUrl, $wordpressData['resource']['url']);
        self::assertSame($safeUrl, $requestContext['url']);
        self::assertStringContainsString($safeUrl, $context['description']);
        self::assertStringContainsString($safeUrl, $log['message']);
        self::assertSame($safeUrl, $logContext['request']['url']);
        self::assertStringContainsString($safeUrl, $logContext['description']);

        self::assertStringNotContainsString('review-sentinel', $structuredMessage);
        self::assertStringNotContainsString('review-sentinel', $response['data']['resource']['url']);
        self::assertStringNotContainsString('review-sentinel', $context['description']);
        self::assertStringNotContainsString('review-sentinel', $log['message']);
    }

    public function testDefaultContextRedactsUrlCredentials(): void
    {
        $url = 'https://user:review-sentinel@example.test/?token=review-sentinel';
        $message = sprintf('%s %s', $this->faker->sentence(), $url);
        $safeMessage = str_replace(
            $url,
            'https://(redacted)@example.test/?token=(redacted)',
            $message,
        );
        $exception = new WordPressApiException($message, 502, [
            'code' => $this->faker->slug(),
            'message' => $message,
            'data' => ['status' => 502],
        ]);
        $context = $exception->getContext();
        $log = $exception->toLogArray();
        $contextResponse = $context['response'] ?? null;
        $logContext = $log['context'] ?? null;

        self::assertSame($message, $exception->getMessage());
        self::assertIsArray($contextResponse);
        self::assertIsArray($logContext);
        self::assertIsArray($logContext['response'] ?? null);
        self::assertIsString($contextResponse['message'] ?? null);
        self::assertIsString($logContext['response']['message'] ?? null);
        self::assertSame($safeMessage, $contextResponse['message']);
        self::assertSame($safeMessage, $logContext['response']['message']);
        self::assertStringNotContainsString('review-sentinel', $contextResponse['message']);
        self::assertStringNotContainsString('review-sentinel', $logContext['response']['message']);
    }

    public function testUrlRedactionHandlesQueryEdgeCases(): void
    {
        $semicolonAndFragmentUrl = 'https://example.test/?safe=1;token=review-sentinel#access_token=review-sentinel&keep=visible';
        $encodedSensitiveKeyUrl = 'https://example.test/?safe=2&%61ccess_token=review-sentinel';
        $prefix = $this->faker->sentence();
        $message = sprintf('%s %s %s', $prefix, $semicolonAndFragmentUrl, $encodedSensitiveKeyUrl);
        $safeMessage = sprintf(
            '%s https://example.test/?safe=1;token=(redacted)#access_token=(redacted)&keep=visible https://example.test/?safe=2&%%61ccess_token=(redacted)',
            $prefix,
        );
        $exception = new WordPressApiException($message);
        $structuredMessage = $exception->toArray()['message'] ?? null;

        self::assertSame($message, $exception->getMessage());
        self::assertIsString($structuredMessage);
        self::assertSame($safeMessage, $structuredMessage);
        self::assertStringNotContainsString('review-sentinel', $structuredMessage);
    }

    public function testContextRedactsWpSecretsKeepsRedactor(): void
    {
        $previousRedactor = AbstractContextAwareException::getRedactor();
        $applicationRedactor = new class implements ContextRedactorInterface {
            /**
             * @param array<array-key, mixed> $context
             * @return array<string, mixed>
             */
            public function redact(array $context): array
            {
                $redacted = [];
                foreach ($context as $key => $value) {
                    if (is_string($key)) {
                        $redacted[$key] = $value;
                    }
                }

                $redacted['application_redactor'] = 'active';

                return $redacted;
            }
        };
        $secret = $this->faker->password(24);

        AbstractContextAwareException::setRedactor($applicationRedactor);

        try {
            $exception = new WordPressApiException(
                $this->faker->sentence(),
                data: ['data' => ['wordpress_application_password' => $secret]],
                context: new ExceptionContext([
                    'application_password' => $secret,
                    'wordpress_application_password' => $secret,
                    'nested' => ['app_password' => $secret],
                ]),
            );
            $context = $exception->getContext();
            $nestedContext = $context['nested'] ?? [];
            $responseData = $exception->toArray()['wordpress_data'] ?? [];
            $logContext = $exception->toLogArray()['context'] ?? [];

            self::assertIsArray($nestedContext);
            self::assertIsArray($responseData);
            self::assertIsArray($logContext);
            self::assertSame($applicationRedactor, AbstractContextAwareException::getRedactor());
            self::assertSame('active', $context['application_redactor'] ?? null);
            self::assertSame('(redacted)', $context['application_password'] ?? null);
            self::assertSame('(redacted)', $context['wordpress_application_password'] ?? null);
            self::assertSame('(redacted)', $nestedContext['app_password'] ?? null);
            self::assertSame(
                '(redacted)',
                $responseData['wordpress_application_password'] ?? null,
            );
            self::assertSame('(redacted)', $logContext['wordpress_application_password'] ?? null);
        } finally {
            AbstractContextAwareException::setRedactor($previousRedactor);
        }
    }

    public function testToArrayWithoutPayload(): void
    {
        $exception = new WordPressApiException($this->faker->sentence());

        $array = $exception->toArray();

        self::assertNull($array['status_code']);
        self::assertNull($array['wordpress_code']);
        self::assertNull($array['wordpress_data']);
        self::assertNull($array['response']);
    }

    public function testToArrayReportsPrevious(): void
    {
        $exception = new WordPressApiException(
            $this->faker->sentence(),
            500,
            null,
            new LogicException($this->faker->sentence()),
        );

        self::assertSame(LogicException::class, $exception->toArray()['previous']);
    }
}
