<?php

namespace Dozuki\Lib\ValueObjects\Http;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class PathRedactionSet {
    /**
     * @param string          $path The URL path value to match for redaction target.
     * @param RedactionItem[] $redactions The redaction set.
     * @param bool            $glob Whether to glob match the URL path.
     */
    public function __construct(
        public readonly string $path,
        public readonly array $redactions,
        public readonly bool $glob = false,
    ) {}
}
