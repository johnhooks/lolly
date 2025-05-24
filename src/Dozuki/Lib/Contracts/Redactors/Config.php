<?php

declare(strict_types=1);

namespace Dozuki\Lib\Contracts\Redactors;

use Dozuki\Psr\Http\Message\UriInterface;
use Dozuki\Lib\ValueObjects\Http\RedactionItem;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

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
