<?php

declare(strict_types=1);

namespace Lolly\Lib\Contracts\Redactors;

use Lolly\Lib\ValueObjects\Http\RedactionItem;
use Lolly\Psr;
use Lolly\Psr\Http\Message\MessageInterface;
use Lolly\Psr\Http\Message\UriInterface;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * HttpMessage interface.
 *
 * Provides methods to redact HTTP messages.
 *
 * @package Lolly
 */
interface HttpMessage {
    /**
     * Redact sensitive information from a HTTP message.
     *
     * @param UriInterface|string $url The URL of the HTTP message to redact.
     * @param MessageInterface    $message The HTTP message to redact.
     * @param RedactionItem[]     $redactions Optionally, additional redaction items to apply.
     *
     * @return MessageInterface The redacted HTTP message.
     */
    public function redact( UriInterface|string $url, MessageInterface $message, array $redactions = [] ): MessageInterface;
}
