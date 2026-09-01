<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk;

use JOOservices\Client\Resilience\RetryConfig;
use JOOservices\Client\Request\RequestBuilder;
use JOOservices\WordPress\Sdk\Contracts\ResponseDecoderInterface;
use JOOservices\WordPress\Sdk\Http\ClientFactory;
use JOOservices\WordPress\Sdk\Http\ErrorMapper;
use JOOservices\WordPress\Sdk\Http\ResponseDecoder;
use JOOservices\WordPress\Sdk\Services\ApplicationPasswordsService;
use JOOservices\WordPress\Sdk\Services\AbilitiesService;
use JOOservices\WordPress\Sdk\Services\AutosavesService;
use JOOservices\WordPress\Sdk\Services\BlockDirectoryService;
use JOOservices\WordPress\Sdk\Services\BlockRendererService;
use JOOservices\WordPress\Sdk\Services\BlocksService;
use JOOservices\WordPress\Sdk\Services\BlockTypesService;
use JOOservices\WordPress\Sdk\Services\CategoriesService;
use JOOservices\WordPress\Sdk\Services\CommentsService;
use JOOservices\WordPress\Sdk\Services\CustomEndpointService;
use JOOservices\WordPress\Sdk\Services\DiscoveryService;
use JOOservices\WordPress\Sdk\Services\EditorService;
use JOOservices\WordPress\Sdk\Services\FontsService;
use JOOservices\WordPress\Sdk\Services\GlobalStylesService;
use JOOservices\WordPress\Sdk\Services\MediaService;
use JOOservices\WordPress\Sdk\Services\MenuLocationsService;
use JOOservices\WordPress\Sdk\Services\NavigationsService;
use JOOservices\WordPress\Sdk\Services\NavMenuItemsService;
use JOOservices\WordPress\Sdk\Services\NavMenusService;
use JOOservices\WordPress\Sdk\Services\PagesService;
use JOOservices\WordPress\Sdk\Services\PatternsService;
use JOOservices\WordPress\Sdk\Services\PluginsService;
use JOOservices\WordPress\Sdk\Services\PostsService;
use JOOservices\WordPress\Sdk\Services\PostTypesService;
use JOOservices\WordPress\Sdk\Services\RevisionsService;
use JOOservices\WordPress\Sdk\Services\SearchService;
use JOOservices\WordPress\Sdk\Services\SettingsService;
use JOOservices\WordPress\Sdk\Services\SidebarsService;
use JOOservices\WordPress\Sdk\Services\SiteHealthService;
use JOOservices\WordPress\Sdk\Services\StatusesService;
use JOOservices\WordPress\Sdk\Services\TagsService;
use JOOservices\WordPress\Sdk\Services\TaxonomiesService;
use JOOservices\WordPress\Sdk\Services\TemplatePartsService;
use JOOservices\WordPress\Sdk\Services\TemplatesService;
use JOOservices\WordPress\Sdk\Services\ThemesService;
use JOOservices\WordPress\Sdk\Services\IconsService;
use JOOservices\WordPress\Sdk\Services\UtilityService;
use JOOservices\WordPress\Sdk\Services\UsersService;
use JOOservices\WordPress\Sdk\Services\WidgetsService;
use JOOservices\WordPress\Sdk\Services\WidgetTypesService;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\ContentBuilder;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Client\ClientInterface;
use Psr\Log\LoggerInterface;

/**
 * Facade and entry point for the WordPress SDK.
 *
 * ```
 * $wordpress = WordPressService::create(
 *     baseUrl: 'https://example.com',
 *     username: 'publisher',
 *     password: 'xxxx xxxx xxxx xxxx',
 * );
 *
 * $posts = $wordpress->posts()->list();
 * ```
 *
 * Services are instantiated lazily on first access and cached per facade
 * instance. Advanced use: build the dependencies yourself and pass them to
 * the constructor.
 */
final class WordPressService
{
    /**
     * @var array<string, class-string>
     */
    private const REGISTRY = [
        'posts' => PostsService::class,
        'pages' => PagesService::class,
        'comments' => CommentsService::class,
        'users' => UsersService::class,
        'media' => MediaService::class,
        'categories' => CategoriesService::class,
        'tags' => TagsService::class,
        'search' => SearchService::class,
        'taxonomies' => TaxonomiesService::class,
        'postTypes' => PostTypesService::class,
        'statuses' => StatusesService::class,
        'settings' => SettingsService::class,
        'applicationPasswords' => ApplicationPasswordsService::class,
        'discovery' => DiscoveryService::class,
        'custom' => CustomEndpointService::class,
        'revisions' => RevisionsService::class,
        'plugins' => PluginsService::class,
        'themes' => ThemesService::class,
        'blocks' => BlocksService::class,
        'blockTypes' => BlockTypesService::class,
        'blockRenderer' => BlockRendererService::class,
        'blockDirectory' => BlockDirectoryService::class,
        'menuLocations' => MenuLocationsService::class,
        'navigations' => NavigationsService::class,
        'navMenus' => NavMenusService::class,
        'navMenuItems' => NavMenuItemsService::class,
        'templates' => TemplatesService::class,
        'templateParts' => TemplatePartsService::class,
        'globalStyles' => GlobalStylesService::class,
        'widgets' => WidgetsService::class,
        'widgetTypes' => WidgetTypesService::class,
        'sidebars' => SidebarsService::class,
        'siteHealth' => SiteHealthService::class,
        'autosaves' => AutosavesService::class,
        'patterns' => PatternsService::class,
        'fonts' => FontsService::class,
        'editor' => EditorService::class,
        'abilities' => AbilitiesService::class,
        'icons' => IconsService::class,
        'utility' => UtilityService::class,
    ];

    /**
     * @var array<string, object>
     */
    private array $services = [];

    public function __construct(
        private readonly ClientInterface $client,
        private readonly RequestBuilder $requestBuilder,
        private readonly ResponseDecoderInterface $decoder,
        private readonly ErrorMapper $errorMapper,
    ) {}

    /**
     * Creates the SDK from credentials — the standard entry point.
     */
    public static function create(
        string $baseUrl,
        string $username = '',
        string $password = '',
        float $timeout = 30.0,
        float $connectTimeout = 10.0,
        ?RetryConfig $retry = null,
        ?LoggerInterface $logger = null,
        bool $allowInsecureHttp = false,
    ): self {
        return self::fromConfig(new Config(
            baseUrl: $baseUrl,
            username: $username,
            password: $password,
            timeout: $timeout,
            connectTimeout: $connectTimeout,
            retry: $retry,
            logger: $logger,
            allowInsecureHttp: $allowInsecureHttp,
        ));
    }

    public static function fromConfig(Config $config): self
    {
        $psr17 = new Psr17Factory();

        return new self(
            (new ClientFactory())->create($config),
            new RequestBuilder($psr17, $psr17, $psr17),
            new ResponseDecoder($config->logger),
            new ErrorMapper(),
        );
    }

    public function posts(): PostsService
    {
        /** @var PostsService */
        return $this->service('posts');
    }

    public function pages(): PagesService
    {
        /** @var PagesService */
        return $this->service('pages');
    }

    public function comments(): CommentsService
    {
        /** @var CommentsService */
        return $this->service('comments');
    }

    public function users(): UsersService
    {
        /** @var UsersService */
        return $this->service('users');
    }

    public function media(): MediaService
    {
        /** @var MediaService */
        return $this->service('media');
    }

    public function categories(): CategoriesService
    {
        /** @var CategoriesService */
        return $this->service('categories');
    }

    public function tags(): TagsService
    {
        /** @var TagsService */
        return $this->service('tags');
    }

    public function search(): SearchService
    {
        /** @var SearchService */
        return $this->service('search');
    }

    public function taxonomies(): TaxonomiesService
    {
        /** @var TaxonomiesService */
        return $this->service('taxonomies');
    }

    public function postTypes(): PostTypesService
    {
        /** @var PostTypesService */
        return $this->service('postTypes');
    }

    public function statuses(): StatusesService
    {
        /** @var StatusesService */
        return $this->service('statuses');
    }

    public function settings(): SettingsService
    {
        /** @var SettingsService */
        return $this->service('settings');
    }

    public function applicationPasswords(): ApplicationPasswordsService
    {
        /** @var ApplicationPasswordsService */
        return $this->service('applicationPasswords');
    }

    public function discovery(): DiscoveryService
    {
        /** @var DiscoveryService */
        return $this->service('discovery');
    }

    public function custom(): CustomEndpointService
    {
        /** @var CustomEndpointService */
        return $this->service('custom');
    }

    public function revisions(): RevisionsService
    {
        /** @var RevisionsService */
        return $this->service('revisions');
    }

    public function plugins(): PluginsService
    {
        /** @var PluginsService */
        return $this->service('plugins');
    }

    public function themes(): ThemesService
    {
        /** @var ThemesService */
        return $this->service('themes');
    }

    public function blocks(): BlocksService
    {
        /** @var BlocksService */
        return $this->service('blocks');
    }

    public function blockTypes(): BlockTypesService
    {
        /** @var BlockTypesService */
        return $this->service('blockTypes');
    }

    public function blockRenderer(): BlockRendererService
    {
        /** @var BlockRendererService */
        return $this->service('blockRenderer');
    }

    public function blockDirectory(): BlockDirectoryService
    {
        /** @var BlockDirectoryService */
        return $this->service('blockDirectory');
    }

    public function menuLocations(): MenuLocationsService
    {
        /** @var MenuLocationsService */
        return $this->service('menuLocations');
    }

    public function navigations(): NavigationsService
    {
        /** @var NavigationsService */
        return $this->service('navigations');
    }

    public function navMenus(): NavMenusService
    {
        /** @var NavMenusService */
        return $this->service('navMenus');
    }

    public function navMenuItems(): NavMenuItemsService
    {
        /** @var NavMenuItemsService */
        return $this->service('navMenuItems');
    }

    public function templates(): TemplatesService
    {
        /** @var TemplatesService */
        return $this->service('templates');
    }

    public function templateParts(): TemplatePartsService
    {
        /** @var TemplatePartsService */
        return $this->service('templateParts');
    }

    public function globalStyles(): GlobalStylesService
    {
        /** @var GlobalStylesService */
        return $this->service('globalStyles');
    }

    public function widgets(): WidgetsService
    {
        /** @var WidgetsService */
        return $this->service('widgets');
    }

    public function widgetTypes(): WidgetTypesService
    {
        /** @var WidgetTypesService */
        return $this->service('widgetTypes');
    }

    public function sidebars(): SidebarsService
    {
        /** @var SidebarsService */
        return $this->service('sidebars');
    }

    public function siteHealth(): SiteHealthService
    {
        /** @var SiteHealthService */
        return $this->service('siteHealth');
    }

    public function autosaves(): AutosavesService
    {
        /** @var AutosavesService */
        return $this->service('autosaves');
    }

    public function patterns(): PatternsService
    {
        /** @var PatternsService */
        return $this->service('patterns');
    }

    public function fonts(): FontsService
    {
        /** @var FontsService */
        return $this->service('fonts');
    }

    public function editor(): EditorService
    {
        /** @var EditorService */
        return $this->service('editor');
    }

    public function abilities(): AbilitiesService
    {
        /** @var AbilitiesService */
        return $this->service('abilities');
    }

    public function icons(): IconsService
    {
        /** @var IconsService */
        return $this->service('icons');
    }

    public function utility(): UtilityService
    {
        /** @var UtilityService */
        return $this->service('utility');
    }

    /**
     * A ContentBuilder wired with the media service (enables uploads).
     */
    public function contentBuilder(): ContentBuilder
    {
        return (new ContentBuilder())->setMediaService($this->media());
    }

    /**
     * @param string $key service registry key
     */
    private function service(string $key): object
    {
        return $this->services[$key] ??= $this->instantiate($key);
    }

    private function instantiate(string $key): object
    {
        $service = new ($this->registryClass($key))(
            $this->client,
            $this->requestBuilder,
            $this->decoder,
            $this->errorMapper,
        );

        if ($service instanceof PostsService) {
            $service->setMediaService($this->media());
        }

        return $service;
    }

    /**
     * @return class-string
     */
    private function registryClass(string $key): string
    {
        return self::REGISTRY[$key];
    }
}
