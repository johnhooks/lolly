<?php

namespace Lolly\Lib\Contracts\Whitelist;

use Lolly\Psr\Http\Message\UriInterface;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Config interface.
 *
 * Provides methods to handle HTTP logging whitelist feature checks and
 * URL whitelist validations.
 *
 * @package Lolly
 */
interface Config {
    /**
     * Check whether the HTTP logging whitelist feature is enabled.
     *
     * @return bool Whether the HTTP logging whitelist feature is enabled.
     */
    public function is_whitelist_enabled(): bool;

    /**
     * Check whether the URL is whitelisted.
     *
     * @param UriInterface|string $url The URL to check for a whitelist target match.
     *
     * @return bool Whether the URL is whitelisted.
     */
    public function is_http_url_whitelisted( UriInterface|string $url ): bool;
}
