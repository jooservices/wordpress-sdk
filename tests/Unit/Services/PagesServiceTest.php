<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Services;

use JOOservices\Client\Testing\TestResponse;
use JOOservices\Client\Testing\TestResponseSequence;
use JOOservices\WordPress\Sdk\Data\Page;
use JOOservices\WordPress\Sdk\Tests\TestCase;

final class PagesServiceTest extends TestCase
{
    public function testRoutesToPagesEndpoint(): void
    {
        $title = $this->faker->sentence(2);
        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::make(200, [], json_encode([
            ['id' => 3, 'type' => 'page', 'title' => ['rendered' => $title]],
        ], JSON_THROW_ON_ERROR)));
        $this->httpFakes()->respond('GET', '*wp/v2/pages*', $sequence);

        $pages = $this->wordPress()->pages()->list();
        self::assertInstanceOf(Page::class, $pages->all()[0]);
        self::assertSame($title, $pages->all()[0]->title?->rendered);
        self::assertSame('/wp-json/wp/v2/pages', $this->lastRequest()->getUri()->getPath());
    }

    public function testProvidesRevisionsAndAutosaves(): void
    {
        $revisions = new TestResponseSequence();
        $revisions->push(TestResponse::json([['id' => 4]]));
        $this->httpFakes()->respond('GET', '*wp/v2/pages/3/revisions*', $revisions);
        self::assertSame([['id' => 4]], $this->wordPress()->pages()->revisions(3)->list());

        $autosaves = new TestResponseSequence();
        $autosaves->push(TestResponse::json([['id' => 5]]));
        $this->httpFakes()->respond('GET', '*wp/v2/pages/3/autosaves*', $autosaves);
        self::assertSame([['id' => 5]], $this->wordPress()->pages()->autosaves(3)->list());
    }
}
