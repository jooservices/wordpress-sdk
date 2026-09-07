<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Support\ContentBuilder\Blocks\Core;

use JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core\Code;
use JOOservices\WordPress\Sdk\Tests\TestCase;

final class CodeTest extends TestCase
{
    public function testEscapesContent(): void
    {
        self::assertSame(
            "<!-- wp:code -->\n<pre class=\"wp-block-code\"><code>&lt;?php echo &#039;x&#039;; ?&gt;</code></pre>\n<!-- /wp:code -->",
            (new Code("<?php echo 'x'; ?>"))->render(),
        );
    }
}
