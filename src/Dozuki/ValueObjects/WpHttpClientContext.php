<?php

declare(strict_types=1);

namespace Dozuki\ValueObjects;

use JsonSerializable;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class WpHttpClientContext.
 *
 * Value object representing a WP HTTP client request/response, the arguments
 * provided to the `http_api_debug` action hook.
 */
class WpHttpClientContext implements JsonSerializable {
    /**
     * @param array<string,mixed>|WP_Error $response     HTTP response or WP_Error object.
     * @param string                       $context      Context under which the hook is fired.
     * @param string                       $transport    HTTP transport used.
     * @param array<string,mixed>          $request_args HTTP request arguments.
     * @param string                       $url          The request URL.
     */
    public function __construct(
        public readonly array|WP_Error $response,
        public readonly string $context,
        public readonly string $transport,
        public readonly array $request_args,
        public readonly string $url,
    ) {}

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array {
        return [
            'response'     => $this->response,
            'context'      => $this->context,
            'transport'    => $this->transport,
            'request_args' => $this->request_args,
            'url'          => $this->url,
        ];
    }
}
