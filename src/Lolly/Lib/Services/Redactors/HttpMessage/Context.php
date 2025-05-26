<?php

namespace Lolly\Lib\Services\Redactors\HttpMessage;

use Lolly\Lib\ValueObjects\Http\RedactionItem;
use Lolly\Psr\Http\Message\UriInterface;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Context class.
 *
 * @package Lolly
 */
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
