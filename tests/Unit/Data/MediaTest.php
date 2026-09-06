<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Data;

use JOOservices\WordPress\Sdk\Data\Media;
use JOOservices\WordPress\Sdk\Tests\TestCase;

final class MediaTest extends TestCase
{
    public function testHydratesAttachmentFields(): void
    {
        $media = Media::from([
            'id' => '3',
            'alt_text' => $this->faker->words(2, true),
            'mime_type' => 'image/png',
            'source_url' => $this->faker->url(),
            'post' => 42,
            'date_gmt' => '2026-08-29T10:00:00',
            'modified' => '2026-08-29T11:00:00',
            'comment_status' => 'open',
            'meta' => ['camera' => 'x'],
        ]);

        self::assertSame(3, $media->id);
        self::assertSame(42, $media->post);
        self::assertSame('open', $media->comment_status);
        self::assertSame(['camera' => 'x'], $media->meta);
    }
}
