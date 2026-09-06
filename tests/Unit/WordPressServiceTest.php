<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit;

use JOOservices\Client\Resilience\RetryConfig;
use JOOservices\Client\Testing\TestResponse;
use JOOservices\Client\Testing\TestResponseSequence;
use JOOservices\WordPress\Sdk\Config;
use JOOservices\WordPress\Sdk\Http\ClientFactory;
use JOOservices\WordPress\Sdk\Tests\TestCase;
use JOOservices\WordPress\Sdk\WordPressService;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionProperty;

final class WordPressServiceTest extends TestCase
{
    public function testCreateBuildsWorkingFacade(): void
    {
        $wordPress = $this->wordPress();

        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::json(['id' => 1, 'title' => ['rendered' => 'A']]));
        $this->httpFakes()->respond('GET', '*wp/v2/posts/1*', $sequence);

        self::assertSame(1, $wordPress->posts()->get(1)->id);
    }

    public function testFromConfigBuildsWorkingFacade(): void
    {
        $fromConfig = WordPressService::fromConfig(new Config(
            'https://example.test',
            'admin',
            'pass',
            retry: new RetryConfig(maxAttempts: 1),
        ));

        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::json(['id' => 1, 'title' => ['rendered' => 'A']]));
        $this->httpFakes()->respond('GET', '*wp/v2/posts/1*', $sequence);

        self::assertSame(1, $fromConfig->posts()->get(1)->id);
    }

    public function testCreateRejectsInvalidUrl(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        WordPressService::create('not a url');
    }

    public function testServicesAreLazilyCachedPerFacade(): void
    {
        $wordPress = $this->wordPress();

        self::assertSame($wordPress->posts(), $wordPress->posts());
        self::assertSame($wordPress->media(), $wordPress->media());
    }

    public function testEveryAccessorReturnsItsDeclaredServiceType(): void
    {
        $wordPress = $this->wordPress();

        $accessors = [
            'posts', 'pages', 'comments', 'users', 'media', 'categories', 'tags',
            'search', 'taxonomies', 'postTypes', 'statuses', 'settings',
            'applicationPasswords', 'discovery', 'custom', 'revisions', 'plugins',
            'themes', 'blocks', 'blockTypes', 'blockRenderer', 'blockDirectory',
            'menuLocations', 'navigations', 'navMenus', 'navMenuItems', 'templates',
            'templateParts', 'globalStyles', 'widgets', 'widgetTypes', 'sidebars',
            'siteHealth',
            'autosaves', 'patterns', 'fonts', 'editor', 'abilities', 'icons',
            'utility',
        ];

        foreach ($accessors as $accessor) {
            $returnType = (new ReflectionMethod(WordPressService::class, $accessor))->getReturnType();

            self::assertInstanceOf(ReflectionNamedType::class, $returnType);
            self::assertSame($returnType->getName(), $wordPress->{$accessor}()::class);
        }
    }

    public function testPostsServiceReceivesMediaServiceForBuilder(): void
    {
        $wordPress = $this->wordPress();

        $builder = $wordPress->posts()->builder();

        $mediaService = (new ReflectionProperty($builder, 'mediaService'))->getValue($builder);

        self::assertSame($wordPress->media(), $mediaService);
    }

    public function testContentBuilderIsWiredWithMediaService(): void
    {
        $wordPress = $this->wordPress();

        $builder = $wordPress->contentBuilder()->text($this->faker->sentence());

        self::assertStringContainsString('wp:paragraph', $builder->render());
    }

    public function testFromClientUsesInjectedTransport(): void
    {
        $config = new Config(
            'https://example.test',
            'admin',
            'pass',
            retry: new RetryConfig(maxAttempts: 1),
        );
        $client = (new ClientFactory())->create($config);
        $wordPress = WordPressService::fromClient($client);

        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::json(['id' => 4, 'title' => ['rendered' => $this->faker->word()]]));
        $this->httpFakes()->respond('GET', '*wp/v2/posts/4*', $sequence);

        self::assertSame(4, $wordPress->posts()->get(4)->id);
    }
}
