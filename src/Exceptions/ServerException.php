<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Exceptions;

/** HTTP 5xx, or an undecodable/HTML response from the WordPress site. */
final class ServerException extends WordPressApiException {}
