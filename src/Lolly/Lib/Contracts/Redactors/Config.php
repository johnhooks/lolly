<?php

declare(strict_types=1);

namespace Lolly\Lib\Contracts\Redactors;

use Lolly\Psr\Http\Message\UriInterface;
use Lolly\Lib\ValueObjects\Http\RedactionItem;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Config interface.
 *
 * Provides methods to handle HTTP logging redaction feature checks and
 * URL redaction items.
 *
 * @package Lolly
 */
interface Config {
    /**
     * Check whether the HTTP logging redaction feature is enabled.
     *
     * @return bool Whether the HTTP logging redaction feature is enabled.
     */
    public function is_http_redactions_enabled(): bool;

    /**
     * Get the redaction items for a specific URL.
     *
     * @param UriInterface|string $url The URL of the HTTP message to redact.
     *
     * @return RedactionItem[] The HTTP redaction items for the `url`.
     */
    public function get_http_redactions( UriInterface|string $url ): array;
}
