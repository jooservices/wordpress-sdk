<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Services;

use JOOservices\Client\Testing\TestResponse;
use JOOservices\Client\Testing\TestResponseSequence;
use JOOservices\WordPress\Sdk\Tests\TestCase;

final class SiteHealthServiceTest extends TestCase
{
    public function testRunsHealthChecks(): void
    {
        foreach ([
            'background-updates' => 'backgroundUpdates',
            'loopback-requests' => 'loopbackRequests',
            'https-status' => 'httpsStatus',
            'dotorg-communication' => 'dotOrgCommunication',
            'authorization-header' => 'authorizationHeader',
            'page-cache' => 'pageCache',
        ] as $test => $method) {
            $sequence = new TestResponseSequence();
            $sequence->push(TestResponse::json(['test' => $test, 'status' => 'ok']));
            $this->httpFakes()->respond('GET', '*wp-site-health/v1/tests/' . $test . '*', $sequence);

            self::assertSame(['test' => $test, 'status' => 'ok'], $this->wordPress()->siteHealth()->{$method}());
        }

        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::json(['status' => 'good']));
        $this->httpFakes()->respond('GET', '*wp-site-health/v1/directory-sizes*', $sequence);
        self::assertSame(['status' => 'good'], $this->wordPress()->siteHealth()->directorySizes());
    }
}
