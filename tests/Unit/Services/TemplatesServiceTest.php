<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Services;

use JOOservices\Client\Testing\TestResponse;
use JOOservices\Client\Testing\TestResponseSequence;
use JOOservices\WordPress\Sdk\Tests\TestCase;

final class TemplatesServiceTest extends TestCase
{
    public function testLooksUpTemplates(): void
    {
        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::json(['id' => 'theme//index']));
        $this->httpFakes()->respond('GET', '*wp/v2/templates/lookup*', $sequence);

        self::assertSame(['id' => 'theme//index'], $this->wordPress()->templates()->lookup(['slug' => 'index']));
    }
}
