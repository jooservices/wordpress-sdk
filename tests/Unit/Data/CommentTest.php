<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Data;

use JOOservices\WordPress\Sdk\Data\Comment;
use JOOservices\WordPress\Sdk\Tests\TestCase;

final class CommentTest extends TestCase
{
    public function testHydratesEditContextAuthorFields(): void
    {
        $email = $this->faker->email();
        $comment = Comment::from([
            'id' => 1,
            'post' => 42,
            'author_name' => $this->faker->name(),
            'author_email' => $email,
            'author_ip' => '127.0.0.1',
            'author_user_agent' => 'PHPUnit',
            'content' => ['rendered' => '<p>Hi</p>'],
        ]);

        self::assertSame($email, $comment->author_email);
        self::assertSame('127.0.0.1', $comment->author_ip);
        self::assertSame('PHPUnit', $comment->author_user_agent);
    }
}
