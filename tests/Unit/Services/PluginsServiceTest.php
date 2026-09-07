<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Services;

use JOOservices\Client\Testing\TestResponse;
use JOOservices\Client\Testing\TestResponseSequence;
use JOOservices\WordPress\Sdk\Tests\TestCase;

final class PluginsServiceTest extends TestCase
{
    public function testSupportsCrudAndMultiSegmentPluginPaths(): void
    {
        $list = new TestResponseSequence();
        $list->push(TestResponse::json(['plugins' => []]));
        $this->httpFakes()->respond('GET', '*wp/v2/plugins*', $list);
        self::assertSame(['plugins' => []], $this->wordPress()->plugins()->list());

        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::json(['plugin' => 'akismet/akismet.php']));
        $this->httpFakes()->respond('GET', '*wp/v2/plugins/akismet/akismet.php*', $sequence);
        self::assertSame(['plugin' => 'akismet/akismet.php'], $this->wordPress()->plugins()->get('akismet/akismet.php'));
        self::assertSame('/wp-json/wp/v2/plugins/akismet/akismet.php', $this->lastRequest()->getUri()->getPath());

        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::json(['plugin' => 'akismet/akismet.php'], 201));
        $this->httpFakes()->respond('POST', '*wp/v2/plugins*', $sequence);
        self::assertSame(['plugin' => 'akismet/akismet.php'], $this->wordPress()->plugins()->create(['slug' => 'akismet']));

        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::json(['deleted' => true]));
        $this->httpFakes()->respond('DELETE', '*wp/v2/plugins/akismet/akismet.php*', $sequence);
        self::assertSame(['deleted' => true], $this->wordPress()->plugins()->delete('akismet/akismet.php'));
    }

    public function testActivatesAndDeactivatesPlugins(): void
    {
        foreach (['active' => 'activate', 'inactive' => 'deactivate'] as $status => $method) {
            $sequence = new TestResponseSequence();
            $sequence->push(TestResponse::json(['status' => $status]));
            $this->httpFakes()->respond('POST', '*wp/v2/plugins/akismet/akismet.php*', $sequence);
            self::assertSame(['status' => $status], $this->wordPress()->plugins()->{$method}('akismet/akismet.php'));
        }
    }
}
