<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Services;

use JOOservices\Client\Testing\TestResponse;
use JOOservices\Client\Testing\TestResponseSequence;
use JOOservices\WordPress\Sdk\Tests\TestCase;
use JOOservices\WordPress\Sdk\WordPressService;

final class RawEndpointServiceTest extends TestCase
{
    private WordPressService $wordPress;

    protected function setUp(): void
    {
        parent::setUp();
        $this->wordPress = $this->wordPress();
    }

    public function testRawCrudServicesUseTraitSurface(): void
    {
        foreach ([
            'blocks' => ['blocks', 'wp/v2/blocks'],
            'navigations' => ['navigations', 'wp/v2/navigation'],
            'navMenus' => ['navMenus', 'wp/v2/menus'],
            'navMenuItems' => ['navMenuItems', 'wp/v2/menu-items'],
            'templates' => ['templates', 'wp/v2/templates'],
            'templateParts' => ['templateParts', 'wp/v2/template-parts'],
            'widgets' => ['widgets', 'wp/v2/widgets'],
        ] as $label => [$accessor, $path]) {
            $service = $this->wordPress->{$accessor}();

            $list = new TestResponseSequence();
            $list->push(TestResponse::json(['items' => [1]]));
            $this->httpFakes()->respond('GET', '*' . $path . '*', $list);
            self::assertSame(['items' => [1]], $service->list(['per_page' => 5]), $label . ' list');
            $this->assertQuery($this->lastRequest(), ['per_page' => 5]);

            $get = new TestResponseSequence();
            $get->push(TestResponse::json(['id' => 1]));
            $this->httpFakes()->respond('GET', '*' . $path . '/1*', $get);
            self::assertSame(['id' => 1], $service->get(1), $label . ' get');

            $create = new TestResponseSequence();
            $create->push(TestResponse::json(['id' => 2], 201));
            $this->httpFakes()->respond('POST', '*' . $path . '*', $create);
            self::assertSame(['id' => 2], $service->create(['name' => 'x']), $label . ' create');

            $update = new TestResponseSequence();
            $update->push(TestResponse::json(['id' => 2, 'name' => 'y']));
            $this->httpFakes()->respond('POST', '*' . $path . '/2*', $update);
            self::assertSame(['id' => 2, 'name' => 'y'], $service->update(2, ['name' => 'y']), $label . ' update');

            $delete = new TestResponseSequence();
            $delete->push(TestResponse::json(['deleted' => true]));
            $this->httpFakes()->respond('DELETE', '*' . $path . '/2*', $delete);
            self::assertSame(['deleted' => true], $service->delete(2, force: true), $label . ' delete');
            $this->assertQuery($this->lastRequest(), ['force' => 'true']);
        }
    }

    public function testPluginsMultiSegmentPathEncoding(): void
    {
        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::json(['plugin' => 'akismet/akismet.php']));
        $this->httpFakes()->respond('GET', '*wp/v2/plugins/akismet/akismet.php*', $sequence);

        $plugin = $this->wordPress->plugins()->get('akismet/akismet.php');

        self::assertSame(['plugin' => 'akismet/akismet.php'], $plugin);
        self::assertSame('/wp-json/wp/v2/plugins/akismet/akismet.php', $this->lastRequest()->getUri()->getPath());
    }

    public function testPluginsCrud(): void
    {
        $list = new TestResponseSequence();
        $list->push(TestResponse::json(['plugins' => []]));
        $this->httpFakes()->respond('GET', '*wp/v2/plugins*', $list);
        self::assertSame(['plugins' => []], $this->wordPress->plugins()->list());

        $create = new TestResponseSequence();
        $create->push(TestResponse::json(['plugin' => 'akismet/akismet.php'], 201));
        $this->httpFakes()->respond('POST', '*wp/v2/plugins*', $create);
        self::assertSame(['plugin' => 'akismet/akismet.php'], $this->wordPress->plugins()->create(['slug' => 'akismet']));

        $update = new TestResponseSequence();
        $update->push(TestResponse::json(['plugin' => 'akismet/akismet.php', 'status' => 'active']));
        $this->httpFakes()->respond('POST', '*wp/v2/plugins/akismet/akismet.php*', $update);
        self::assertSame(
            ['plugin' => 'akismet/akismet.php', 'status' => 'active'],
            $this->wordPress->plugins()->update('akismet/akismet.php', ['status' => 'active']),
        );

        $delete = new TestResponseSequence();
        $delete->push(TestResponse::json(['deleted' => true]));
        $this->httpFakes()->respond('DELETE', '*wp/v2/plugins/akismet/akismet.php*', $delete);
        self::assertSame(['deleted' => true], $this->wordPress->plugins()->delete('akismet/akismet.php'));
    }

    public function testPluginsActivateAndDeactivate(): void
    {
        $activate = new TestResponseSequence();
        $activate->push(TestResponse::json(['status' => 'active']));
        $this->httpFakes()->respond('POST', '*wp/v2/plugins/akismet/akismet.php*', $activate);

        self::assertSame(['status' => 'active'], $this->wordPress->plugins()->activate('akismet/akismet.php'));
        $this->assertJsonBody($this->lastRequest(), ['status' => 'active']);

        $deactivate = new TestResponseSequence();
        $deactivate->push(TestResponse::json(['status' => 'inactive']));
        $this->httpFakes()->respond('POST', '*wp/v2/plugins/akismet/akismet.php*', $deactivate);

        self::assertSame(['status' => 'inactive'], $this->wordPress->plugins()->deactivate('akismet/akismet.php'));
        $this->assertJsonBody($this->lastRequest(), ['status' => 'inactive']);
    }

    public function testThemesListAndGet(): void
    {
        $list = new TestResponseSequence();
        $list->push(TestResponse::json(['themes' => []]));
        $this->httpFakes()->respond('GET', '*wp/v2/themes*', $list);
        self::assertSame(['themes' => []], $this->wordPress->themes()->list());

        $get = new TestResponseSequence();
        $get->push(TestResponse::json(['stylesheet' => 'twentytwentyfive']));
        $this->httpFakes()->respond('GET', '*wp/v2/themes/twentytwentyfive*', $get);
        self::assertSame(['stylesheet' => 'twentytwentyfive'], $this->wordPress->themes()->get('twentytwentyfive'));
    }

    public function testBlockTypesListWithNamespace(): void
    {
        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::json(['blocks' => []]));
        $this->httpFakes()->respond('GET', '*wp/v2/block-types/core*', $sequence);

        self::assertSame(['blocks' => []], $this->wordPress->blockTypes()->list('core'));
        self::assertSame('/wp-json/wp/v2/block-types/core', $this->lastRequest()->getUri()->getPath());

        $get = new TestResponseSequence();
        $get->push(TestResponse::json(['name' => 'core/paragraph']));
        $this->httpFakes()->respond('GET', '*wp/v2/block-types/core/paragraph*', $get);
        self::assertSame(['name' => 'core/paragraph'], $this->wordPress->blockTypes()->get('core/paragraph'));
    }

    public function testBlockRendererSendsEditContext(): void
    {
        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::json(['rendered' => '<div>Latest</div>']));
        $this->httpFakes()->respond('GET', '*wp/v2/block-renderer/core/latest-posts*', $sequence);

        $rendered = $this->wordPress->blockRenderer()->render('core/latest-posts', ['postsToShow' => 3], 12);

        self::assertSame(['rendered' => '<div>Latest</div>'], $rendered);
        $request = $this->lastRequest();
        $this->assertQuery($request, ['context' => 'edit', 'post_id' => 12]);
    }

    public function testBlockDirectorySearch(): void
    {
        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::json(['results' => []]));
        $this->httpFakes()->respond('GET', '*wp/v2/block-directory/search*', $sequence);

        self::assertSame(['results' => []], $this->wordPress->blockDirectory()->search(['term' => 'gallery']));
        $this->assertQuery($this->lastRequest(), ['term' => 'gallery']);
    }

    public function testMenuLocations(): void
    {
        $list = new TestResponseSequence();
        $list->push(TestResponse::json(['locations' => []]));
        $this->httpFakes()->respond('GET', '*wp/v2/menu-locations*', $list);
        self::assertSame(['locations' => []], $this->wordPress->menuLocations()->list());

        $get = new TestResponseSequence();
        $get->push(TestResponse::json(['location' => 'primary']));
        $this->httpFakes()->respond('GET', '*wp/v2/menu-locations/primary*', $get);
        self::assertSame(['location' => 'primary'], $this->wordPress->menuLocations()->get('primary'));
    }

    public function testGlobalStyles(): void
    {
        $list = new TestResponseSequence();
        $list->push(TestResponse::json(['styles' => []]));
        $this->httpFakes()->respond('GET', '*wp/v2/global-styles*', $list);
        self::assertSame(['styles' => []], $this->wordPress->globalStyles()->list());

        $theme = new TestResponseSequence();
        $theme->push(TestResponse::json(['settings' => []]));
        $this->httpFakes()->respond('GET', '*wp/v2/global-styles/themes/twentytwentyfive*', $theme);
        self::assertSame(['settings' => []], $this->wordPress->globalStyles()->theme('twentytwentyfive'));

        $update = new TestResponseSequence();
        $update->push(TestResponse::json(['id' => 1]));
        $this->httpFakes()->respond('POST', '*wp/v2/global-styles/1*', $update);
        self::assertSame(['id' => 1], $this->wordPress->globalStyles()->update(1, ['settings' => []]));
    }

    public function testWidgetTypesAndSidebars(): void
    {
        $list = new TestResponseSequence();
        $list->push(TestResponse::json(['types' => []]));
        $this->httpFakes()->respond('GET', '*wp/v2/widget-types*', $list);
        self::assertSame(['types' => []], $this->wordPress->widgetTypes()->list());

        $encode = new TestResponseSequence();
        $encode->push(TestResponse::json(['encoded' => 'x']));
        $this->httpFakes()->respond('POST', '*wp/v2/widget-types/paragraph/encode*', $encode);
        self::assertSame(['encoded' => 'x'], $this->wordPress->widgetTypes()->encode('paragraph', ['text' => 'Hi']));

        $sidebars = new TestResponseSequence();
        $sidebars->push(TestResponse::json(['sidebars' => []]));
        $this->httpFakes()->respond('GET', '*wp/v2/sidebars*', $sidebars);
        self::assertSame(['sidebars' => []], $this->wordPress->sidebars()->list());

        $update = new TestResponseSequence();
        $update->push(TestResponse::json(['id' => 'sidebar-1']));
        $this->httpFakes()->respond('POST', '*wp/v2/sidebars/sidebar-1*', $update);
        self::assertSame(['id' => 'sidebar-1'], $this->wordPress->sidebars()->update('sidebar-1', ['widgets' => []]));
    }

    public function testSiteHealth(): void
    {
        foreach ([
            'background-updates' => 'backgroundUpdates',
            'loopback-requests' => 'loopbackRequests',
            'https-status' => 'httpsStatus',
        ] as $test => $method) {
            $sequence = new TestResponseSequence();
            $sequence->push(TestResponse::json(['test' => $test, 'status' => 'ok']));
            $this->httpFakes()->respond('GET', '*wp-site-health/v1/tests/' . $test . '*', $sequence);

            self::assertSame(['test' => $test, 'status' => 'ok'], $this->wordPress->siteHealth()->{$method}());
            self::assertSame(
                '/wp-json/wp-site-health/v1/tests/' . $test,
                $this->lastRequest()->getUri()->getPath(),
            );
        }
    }
}
