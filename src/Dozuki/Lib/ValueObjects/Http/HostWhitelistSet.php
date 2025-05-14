<?php

namespace Dozuki\Lib\ValueObjects\Http;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class HostWhitelistSet {
    /**
     * @param string             $host The URL host value to match for whitelist target.
     * @param PathWhitelistSet[] $paths The URL path whitelist set.
     * @param bool               $glob Whether to glob match the URL host.
     */
    public function __construct(
        public readonly string $host,
        public readonly array $paths,
        public readonly bool $glob = false,
    ) {}
}
