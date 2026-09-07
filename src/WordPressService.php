<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk;

use JOOservices\Client\Resilience\RetryConfig;
use JOOservices\Client\Request\RequestBuilder;
use JOOservices\WordPress\Sdk\Contracts\ResponseDecoderInterface;
use JOOservices\WordPress\Sdk\Http\AbstractService;
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
use JOOservices\WordPress\Sdk\Services\ResourceService;
use JOOservices\WordPress\Sdk\Services\RevisionsService;
use JOOservices\WordPress\Sdk\Services\SearchService;
use JOOservices\WordPress\Sdk\Services\SettingsService;
use JOOservices\WordPress\Sdk\Services\SidebarsService;
use JOOservices\WordPress\Sdk\Services\SiteHealthService;
use JOOservices\WordPress\Sdk\Services\StatusesService;
use JOOservices\WordPress\Sdk\Services\TagsService;
use JOOservices\WordPress\Sdk\Services\TaxonomiesService;
use JOOservices\WordPress\Sdk\Services\TermsService;
use JOOservices\WordPress\Sdk\Services\TemplatePartsService;
use JOOservices\WordPress\Sdk\Services\TemplatesService;
use JOOservices\WordPress\Sdk\Services\ThemesService;
use JOOservices\WordPress\Sdk\Services\IconsService;
use JOOservices\WordPress\Sdk\Services\UtilityService;
use JOOservices\WordPress\Sdk\Services\UsersService;
use JOOservices\WordPress\Sdk\Services\WidgetsService;
use JOOservices\WordPress\Sdk\Services\WidgetTypesService;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\ContentBuilder;
use JOOservices\WordPress\Sdk\Support\RestPath;
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
     * @var array<string, AbstractService>
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
        return self::fromClient(
            (new ClientFactory())->create($config),
            logger: $config->logger,
        );
    }

    /**
     * Advanced entry point: inject a pre-built PSR-18 client (bearer, JWT,
     * custom middleware) while the SDK still owns request building, decoding,
     * and error mapping.
     */
    public static function fromClient(
        ClientInterface $client,
        ?RequestBuilder $requestBuilder = null,
        ?ResponseDecoderInterface $decoder = null,
        ?ErrorMapper $errorMapper = null,
        ?LoggerInterface $logger = null,
    ): self {
        $psr17 = new Psr17Factory();

        return new self(
            $client,
            $requestBuilder ?? new RequestBuilder($psr17, $psr17, $psr17),
            $decoder ?? new ResponseDecoder($logger),
            $errorMapper ?? new ErrorMapper(),
        );
    }

    public function posts(): PostsService
    {
        $service = $this->services[PostsService::class] ?? null;

        return $service instanceof PostsService
            ? $service
            : $this->services[PostsService::class] = new PostsService(
                $this->client,
                $this->requestBuilder,
                $this->decoder,
                $this->errorMapper,
                $this->media(),
            );
    }

    public function pages(): PagesService
    {
        return $this->service(PagesService::class);
    }

    public function comments(): CommentsService
    {
        return $this->service(CommentsService::class);
    }

    public function users(): UsersService
    {
        return $this->service(UsersService::class);
    }

    public function media(): MediaService
    {
        return $this->service(MediaService::class);
    }

    public function categories(): CategoriesService
    {
        return $this->service(CategoriesService::class);
    }

    public function tags(): TagsService
    {
        return $this->service(TagsService::class);
    }

    public function search(): SearchService
    {
        return $this->service(SearchService::class);
    }

    public function taxonomies(): TaxonomiesService
    {
        return $this->service(TaxonomiesService::class);
    }

    public function postTypes(): PostTypesService
    {
        return $this->service(PostTypesService::class);
    }

    public function statuses(): StatusesService
    {
        return $this->service(StatusesService::class);
    }

    public function settings(): SettingsService
    {
        return $this->service(SettingsService::class);
    }

    public function applicationPasswords(): ApplicationPasswordsService
    {
        return $this->service(ApplicationPasswordsService::class);
    }

    public function discovery(): DiscoveryService
    {
        return $this->service(DiscoveryService::class);
    }

    public function custom(): CustomEndpointService
    {
        return $this->service(CustomEndpointService::class);
    }

    public function revisions(): RevisionsService
    {
        return $this->service(RevisionsService::class);
    }

    public function plugins(): PluginsService
    {
        return $this->service(PluginsService::class);
    }

    public function themes(): ThemesService
    {
        return $this->service(ThemesService::class);
    }

    public function blocks(): BlocksService
    {
        return $this->service(BlocksService::class);
    }

    public function blockTypes(): BlockTypesService
    {
        return $this->service(BlockTypesService::class);
    }

    public function blockRenderer(): BlockRendererService
    {
        return $this->service(BlockRendererService::class);
    }

    public function blockDirectory(): BlockDirectoryService
    {
        return $this->service(BlockDirectoryService::class);
    }

    public function menuLocations(): MenuLocationsService
    {
        return $this->service(MenuLocationsService::class);
    }

    public function navigations(): NavigationsService
    {
        return $this->service(NavigationsService::class);
    }

    public function navMenus(): NavMenusService
    {
        return $this->service(NavMenusService::class);
    }

    public function navMenuItems(): NavMenuItemsService
    {
        return $this->service(NavMenuItemsService::class);
    }

    public function templates(): TemplatesService
    {
        return $this->service(TemplatesService::class);
    }

    public function templateParts(): TemplatePartsService
    {
        return $this->service(TemplatePartsService::class);
    }

    public function globalStyles(): GlobalStylesService
    {
        return $this->service(GlobalStylesService::class);
    }

    public function widgets(): WidgetsService
    {
        return $this->service(WidgetsService::class);
    }

    public function widgetTypes(): WidgetTypesService
    {
        return $this->service(WidgetTypesService::class);
    }

    public function sidebars(): SidebarsService
    {
        return $this->service(SidebarsService::class);
    }

    public function siteHealth(): SiteHealthService
    {
        return $this->service(SiteHealthService::class);
    }

    public function autosaves(): AutosavesService
    {
        return $this->service(AutosavesService::class);
    }

    public function patterns(): PatternsService
    {
        return $this->service(PatternsService::class);
    }

    public function fonts(): FontsService
    {
        return $this->service(FontsService::class);
    }

    public function editor(): EditorService
    {
        return $this->service(EditorService::class);
    }

    public function abilities(): AbilitiesService
    {
        return $this->service(AbilitiesService::class);
    }

    public function icons(): IconsService
    {
        return $this->service(IconsService::class);
    }

    public function utility(): UtilityService
    {
        return $this->service(UtilityService::class);
    }

    /**
     * Typed CRUD for a `show_in_rest` custom post type (or any post-schema
     * collection). Bare slugs resolve under `wp/v2/`.
     */
    public function resource(string $restBase): ResourceService
    {
        $path = (new RestPath())->collection($restBase);
        $key = 'resource:' . $path;
        $service = $this->services[$key] ?? null;

        return $service instanceof ResourceService
            ? $service
            : $this->services[$key] = new ResourceService(
                $this->client,
                $this->requestBuilder,
                $this->decoder,
                $this->errorMapper,
                $path,
            );
    }

    /**
     * Typed CRUD for a `show_in_rest` custom taxonomy (or categories/tags
     * by rest_base). Bare slugs resolve under `wp/v2/`.
     *
     * @param bool $hierarchical whether the taxonomy is hierarchical;
     *                           hierarchical taxonomies paginate via
     *                           `page`/`per_page` and ignore `offset`
     */
    public function terms(string $restBase, bool $hierarchical = false): TermsService
    {
        $path = (new RestPath())->collection($restBase);
        $key = 'terms:' . $path . ($hierarchical ? ':hierarchical' : '');
        $service = $this->services[$key] ?? null;

        return $service instanceof TermsService
            ? $service
            : $this->services[$key] = new TermsService(
                $this->client,
                $this->requestBuilder,
                $this->decoder,
                $this->errorMapper,
                $path,
                $hierarchical,
            );
    }

    /**
     * A ContentBuilder wired with the media service (enables uploads).
     */
    public function contentBuilder(): ContentBuilder
    {
        return (new ContentBuilder())->setMediaService($this->media());
    }

    /**
     * @template TService of AbstractService
     *
     * @param class-string<TService> $className
     *
     * @return TService
     */
    private function service(string $className): AbstractService
    {
        $service = $this->services[$className] ?? null;

        if ($service instanceof $className) {
            return $service;
        }

        $service = new $className(
            $this->client,
            $this->requestBuilder,
            $this->decoder,
            $this->errorMapper,
        );
        $this->services[$className] = $service;

        return $service;
    }
}
