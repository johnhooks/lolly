<?php

namespace Lolly\Lib\ValueObjects\Http;

use Lolly\Lib\Enums\HttpRedactionType;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * RedactionItem class.
 *
 * Represents an item to be used for redacting HTTP data.
 *
 * @package Lolly
 */
class RedactionItem {
    /**
     * @param HttpRedactionType $type The type of HTTP redaction.
     * @param string            $value The value to match as a redaction target.
     * @param bool              $should_remove Whether a match be totally removed.
     */
    public function __construct(
        public readonly HttpRedactionType $type,
        public readonly string $value,
        public readonly bool $should_remove = false,
    ) {}
}
