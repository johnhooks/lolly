<?php

namespace Dozuki\Lib\ValueObjects\Http;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class PathWhitelistSet {
    /**
     * @param string $path The URL path value to match for whitelist target.
     * @param bool   $glob Whether to glob match the URL path.
     */
    public function __construct(
        public readonly string $path,
        public readonly bool $glob = false,
    ) {}
}
