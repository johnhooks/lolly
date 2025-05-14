<?php

namespace Dozuki\Lib\Services\Redactors\HttpMessage;

use Dozuki\Lib\ValueObjects\Http\RedactionItem;
use Dozuki\Psr\Http\Message\UriInterface;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Context {
    /**
     * @param UriInterface    $url
     * @param RedactionItem[] $redactions
     */
    public function __construct(
        public readonly UriInterface $url,
        public readonly array $redactions,
    ) {}
}
