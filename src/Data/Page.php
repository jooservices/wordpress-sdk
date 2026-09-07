<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Data;

/**
 * WordPress page (`/wp/v2/pages`).
 *
 * Shares the post schema plus hierarchical fields (`parent`, `menu_order`)
 * already present on {@see Post} so custom hierarchical types can reuse it.
 */
final class Page extends Post {}
