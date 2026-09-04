<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Services;

use InvalidArgumentException;
use JOOservices\Client\Testing\TestResponse;
use JOOservices\Client\Testing\TestResponseSequence;
use JOOservices\WordPress\Sdk\Support\CoreRouteSupport;
use JOOservices\WordPress\Sdk\Tests\TestCase;
use JOOservices\WordPress\Sdk\WordPressService;

final class CoreApiServicesTest extends TestCase
{
    private WordPressService $wordPress;

    protected function setUp(): void
    {
        parent::setUp();
        $this->wordPress = $this->wordPress();
    }

    public function testCoreRouteInventoryRejectsUnknownFamilies(): void
    {
        $routes = [
            '/',
            '/batch/v1',
            '/oembed/1.0/embed',
            '/wp/v2/posts',
            '/wp/v2/posts/(?P<id>[\\d]+)',
            '/wp/v2/posts/(?P<id>[\\d]+)/revisions',
            '/wp-site-health/v1/tests/page-cache',
            '/wp-block-editor/v1/export',
            '/wp-abilities/v1/abilities',
            '/plugin/v1/items',
            '/wp/v2/future-resource',
            '/wp/v2/posts/(?P<id>[\\d]+)/brand-new-subroute',
            '/wp-block-editor/v1/brand-new',
        ];

        self::assertSame(
            [
                '/plugin/v1/items',
                '/wp/v2/future-resource',
                '/wp/v2/posts/(?P<id>[\\d]+)/brand-new-subroute',
                '/wp-block-editor/v1/brand-new',
            ],
            (new CoreRouteSupport())->unsupported($routes),
        );

        $templateRoutes = [
            '/wp/v2/templates/(?P<parent>([^\/:<>\*\?"\|]+(?:\/[^\/:<>\*\?"\|]+)?)[\/\w%-]+)/revisions',
            '/wp/v2/templates/(?P<id>([^\/:<>\*\?"\|]+(?:\/[^\/:<>\*\?"\|]+)?)[\/\w%-]+)',
            '/wp/v2/template-parts/(?P<parent>([^\/:<>\*\?"\|]+(?:\/[^\/:<>\*\?"\|]+)?)[\/\w%-]+)/autosaves/(?P<id>[\d]+)',
            '/wp/v2/global-styles/(?P<parent>[\d]+)/revisions',
            '/wp/v2/global-styles/(?P<parent>[\d]+)/revisions/(?P<id>[\d]+)',
        ];
        self::assertSame([], (new CoreRouteSupport())->unsupported($templateRoutes));
    }

    public function testRevisionsAndAutosavesShareResourceAllowlist(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported revision resource: unknown');
        $this->wordPress->revisions()->resource('unknown', 1);
    }

    public function testAutosavesCoverListGetCreateAndValidation(): void
    {
        $resource = $this->wordPress->autosaves()->resource('posts', 7);

        $this->respond('GET', 'wp/v2/posts/7/autosaves', [['id' => 1]]);
        self::assertSame([['id' => 1]], $resource->list(['context' => 'edit']));
        $this->assertQuery($this->lastRequest(), ['context' => 'edit']);

        $this->respond('GET', 'wp/v2/posts/7/autosaves/1', ['id' => 1]);
        self::assertSame(['id' => 1], $resource->get(1));

        $this->respond('POST', 'wp/v2/posts/7/autosaves', ['id' => 2]);
        self::assertSame(['id' => 2], $resource->create(['title' => 'Draft']));

        $this->expectException(InvalidArgumentException::class);
        $this->wordPress->autosaves()->resource('unknown', 1);
    }

    public function testPatternsCoverRegistryDirectoryAndTermCrud(): void
    {
        $patterns = $this->wordPress->patterns();

        foreach ([
            ['patterns', 'GET', 'wp/v2/block-patterns/patterns', ['category' => 1]],
            ['categories', 'GET', 'wp/v2/block-patterns/categories', ['context' => 'view']],
            ['directory', 'GET', 'wp/v2/pattern-directory/patterns', ['search' => 'hero']],
            ['listTerms', 'GET', 'wp/v2/wp_pattern_category', ['per_page' => 10]],
        ] as [$method, $httpMethod, $path, $query]) {
            $this->respond($httpMethod, $path, ['ok' => true]);
            self::assertSame(['ok' => true], $patterns->{$method}($query));
        }

        $this->respond('GET', 'wp/v2/wp_pattern_category/2', ['id' => 2]);
        self::assertSame(['id' => 2], $patterns->getTerm(2));
        $this->respond('POST', 'wp/v2/wp_pattern_category', ['id' => 3]);
        self::assertSame(['id' => 3], $patterns->createTerm(['name' => 'Hero']));
        $this->respond('POST', 'wp/v2/wp_pattern_category/3', ['id' => 3]);
        self::assertSame(['id' => 3], $patterns->updateTerm(3, ['name' => 'Heroes']));
        $this->respond('DELETE', 'wp/v2/wp_pattern_category/3', ['deleted' => true]);
        self::assertSame(['deleted' => true], $patterns->deleteTerm(3, false));
    }

    public function testFontsCoverFamiliesFacesAndCollections(): void
    {
        $fonts = $this->wordPress->fonts();
        $this->respond('GET', 'wp/v2/font-families', ['items' => []]);
        self::assertSame(['items' => []], $fonts->families(['per_page' => 5]));
        $this->respond('GET', 'wp/v2/font-families/1', ['id' => 1]);
        self::assertSame(['id' => 1], $fonts->family(1));
        $this->respond('POST', 'wp/v2/font-families', ['id' => 2]);
        self::assertSame(['id' => 2], $fonts->createFamily(['name' => 'Inter']));
        $this->respond('POST', 'wp/v2/font-families/2', ['id' => 2]);
        self::assertSame(['id' => 2], $fonts->updateFamily(2, ['name' => 'Inter UI']));
        $this->respond('DELETE', 'wp/v2/font-families/2', ['deleted' => true]);
        self::assertSame(['deleted' => true], $fonts->deleteFamily(2, false));

        $this->respond('GET', 'wp/v2/font-families/1/font-faces', ['items' => []]);
        self::assertSame(['items' => []], $fonts->faces(1));
        $this->respond('GET', 'wp/v2/font-families/1/font-faces/4', ['id' => 4]);
        self::assertSame(['id' => 4], $fonts->face(1, 4));
        $this->respond('POST', 'wp/v2/font-families/1/font-faces', ['id' => 5]);
        self::assertSame(['id' => 5], $fonts->createFace(1, ['font_weight' => '400']));
        $this->respond('POST', 'wp/v2/font-families/1/font-faces/5', ['id' => 5]);
        self::assertSame(['id' => 5], $fonts->updateFace(1, 5, ['font_style' => 'normal']));
        $this->respond('DELETE', 'wp/v2/font-families/1/font-faces/5', ['deleted' => true]);
        self::assertSame(['deleted' => true], $fonts->deleteFace(1, 5));

        $this->respond('GET', 'wp/v2/font-collections', ['items' => []]);
        self::assertSame(['items' => []], $fonts->collections());
        $this->respond('GET', 'wp/v2/font-collections/google-fonts', ['slug' => 'google-fonts']);
        self::assertSame(['slug' => 'google-fonts'], $fonts->collection('google-fonts'));
    }

    public function testEditorAbilitiesIconsAndUtilities(): void
    {
        $editor = $this->wordPress->editor();
        foreach ([
            ['urlDetails', 'wp-block-editor/v1/url-details', ['https://example.test/post']],
            ['export', 'wp-block-editor/v1/export', []],
            ['navigationFallback', 'wp-block-editor/v1/navigation-fallback', []],
            ['viewConfig', 'wp/v2/view-config', []],
        ] as [$method, $path, $arguments]) {
            $this->respond('GET', $path, ['ok' => true]);
            self::assertSame(['ok' => true], $editor->{$method}(...$arguments));
        }

        $abilities = $this->wordPress->abilities();
        $this->respond('GET', 'wp-abilities/v1/abilities', ['items' => []]);
        self::assertSame(['items' => []], $abilities->list(['category' => 'core']));
        $this->respond('GET', 'wp-abilities/v1/abilities/core/get-info', ['name' => 'core/get-info']);
        self::assertSame(['name' => 'core/get-info'], $abilities->get('core/get-info'));
        $this->respond('POST', 'wp-abilities/v1/abilities/core/get-info/run', ['ok' => true]);
        self::assertSame(['ok' => true], $abilities->run('core/get-info', ['id' => 1]));
        $this->respond('GET', 'wp-abilities/v1/categories', ['items' => []]);
        self::assertSame(['items' => []], $abilities->categories());
        $this->respond('GET', 'wp-abilities/v1/categories/core', ['slug' => 'core']);
        self::assertSame(['slug' => 'core'], $abilities->category('core'));

        $icons = $this->wordPress->icons();
        $this->respond('GET', 'wp/v2/icons', ['items' => []]);
        self::assertSame(['items' => []], $icons->list());
        $this->respond('GET', 'wp/v2/icons/core', ['items' => []]);
        self::assertSame(['items' => []], $icons->list('core'));
        $this->respond('GET', 'wp/v2/icons/core/home', ['name' => 'home']);
        self::assertSame(['name' => 'home'], $icons->get('core', 'home'));
        $this->respond('GET', 'wp/v2/icon-collections', ['items' => []]);
        self::assertSame(['items' => []], $icons->collections());
        $this->respond('GET', 'wp/v2/icon-collections/core', ['slug' => 'core']);
        self::assertSame(['slug' => 'core'], $icons->collection('core'));

        $utility = $this->wordPress->utility();
        $this->respond('POST', 'batch/v1', ['responses' => []]);
        self::assertSame(['responses' => []], $utility->batch([
            ['method' => 'POST', 'path' => '/wp/v2/posts', 'body' => ['title' => 'Draft']],
        ], 'require-all-validate'));
        $this->respond('GET', 'oembed/1.0/embed', ['html' => '<p>x</p>']);
        self::assertSame(['html' => '<p>x</p>'], $utility->embed('https://example.test/post', ['maxwidth' => 600]));
        $this->respond('GET', 'oembed/1.0/proxy', ['html' => '<p>x</p>']);
        self::assertSame(['html' => '<p>x</p>'], $utility->proxy('https://example.test/post'));
    }

    public function testExpandedExistingCoreServices(): void
    {
        $this->respond('GET', 'wp/v2/users/me/application-passwords/introspect', [
            'uuid' => 'password-uuid', 'name' => 'SDK',
        ]);
        self::assertSame('password-uuid', $this->wordPress->applicationPasswords()->introspect()->uuid);

        $this->respond('POST', 'wp/v2/media/4/post-process', ['id' => 4]);
        self::assertSame(['id' => 4], $this->wordPress->media()->postProcess(4, 'create-image-subsizes'));
        $this->respond('POST', 'wp/v2/media/4/edit', ['id' => 4]);
        self::assertSame(['id' => 4], $this->wordPress->media()->edit(4, ['rotation' => 90]));

        $this->respond('GET', 'wp/v2/templates/lookup', ['id' => 'theme//index']);
        self::assertSame(['id' => 'theme//index'], $this->wordPress->templates()->lookup(['slug' => 'index']));
        $this->respond('GET', 'wp/v2/global-styles/themes/theme/variations', ['items' => []]);
        self::assertSame(['items' => []], $this->wordPress->globalStyles()->variations('theme'));
        $this->respond('POST', 'wp/v2/widget-types/text/render', ['preview' => '<p>x</p>']);
        self::assertSame(['preview' => '<p>x</p>'], $this->wordPress->widgetTypes()->render('text', ['text' => 'x']));

        foreach ([
            'dotOrgCommunication' => 'tests/dotorg-communication',
            'authorizationHeader' => 'tests/authorization-header',
            'pageCache' => 'tests/page-cache',
            'directorySizes' => 'directory-sizes',
        ] as $method => $path) {
            $this->respond('GET', 'wp-site-health/v1/' . $path, ['status' => 'good']);
            self::assertSame(['status' => 'good'], $this->wordPress->siteHealth()->{$method}());
        }
    }

    /** @param array<mixed> $body */
    private function respond(string $method, string $path, array $body): void
    {
        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::json($body));
        $this->httpFakes()->respond($method, '*' . $path . '*', $sequence);
    }
}
