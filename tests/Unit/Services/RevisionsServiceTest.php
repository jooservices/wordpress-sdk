<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Services;

use JOOservices\Client\Testing\TestResponse;
use JOOservices\Client\Testing\TestResponseSequence;
use JOOservices\WordPress\Sdk\Tests\TestCase;

final class RevisionsServiceTest extends TestCase
{
    public function testCreatesScopedResources(): void
    {
        $title = $this->faker->sentence(2);
        $list = new TestResponseSequence();
        $list->push(TestResponse::make(200, [], json_encode([
            ['id' => 1, 'title' => ['rendered' => $title]],
        ], JSON_THROW_ON_ERROR)));
        $this->httpFakes()->respond('GET', '*wp/v2/posts/9/revisions*', $list);

        self::assertSame(
            [['id' => 1, 'title' => ['rendered' => $title]]],
            $this->wordPress()->revisions()->posts(9)->list(),
        );
        self::assertSame('/wp-json/wp/v2/posts/9/revisions', $this->lastRequest()->getUri()->getPath());

        $get = new TestResponseSequence();
        $get->push(TestResponse::json(['id' => 2]));
        $this->httpFakes()->respond('GET', '*wp/v2/pages/3/revisions/2*', $get);
        self::assertSame(['id' => 2], $this->wordPress()->revisions()->pages(3)->get(2));

        $blocks = new TestResponseSequence();
        $blocks->push(TestResponse::json([['id' => 4]]));
        $this->httpFakes()->respond('GET', '*wp/v2/blocks/5/revisions*', $blocks);
        self::assertSame([['id' => 4]], $this->wordPress()->revisions()->blocks(5)->list());

        $template = new TestResponseSequence();
        $template->push(TestResponse::json(['id' => 5]));
        $this->httpFakes()->respond('GET', '*wp/v2/templates/theme%2Findex/revisions*', $template);
        self::assertSame(['id' => 5], $this->wordPress()->revisions()->resource('templates', 'theme/index')->list());

        $delete = new TestResponseSequence();
        $delete->push(TestResponse::json(['deleted' => true]));
        $this->httpFakes()->respond('DELETE', '*wp/v2/posts/9/revisions/2*', $delete);
        self::assertSame(['deleted' => true], $this->wordPress()->revisions()->posts(9)->delete(2));
    }
}
