<?php

declare(strict_types=1);

namespace Dozuki\Lib\Contracts\Redactors;

use Dozuki\Lib\ValueObjects\Http\RedactionItem;
use Dozuki\Psr;
use Dozuki\Psr\Http\Message\MessageInterface;
use Dozuki\Psr\Http\Message\UriInterface;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

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
