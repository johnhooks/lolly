<?php

declare(strict_types=1);

namespace Lolly\ValueObjects;

use JsonSerializable;
use WP_Error;
use WP_REST_Response;
use WP_REST_Request;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class WpRestApiContext.
 *
 * Value object representing a WP REST API request/response, the arguments
 * provided to the `rest_post_dispatch` filter hook.
 *
 * @package Lolly
 */
class WpRestApiContext implements JsonSerializable {
    /**
     * @param WP_REST_Response|WP_Error|mixed $result  Result to send to the client. Usually a `WP_REST_Response|WP_Error`.
     * @param WP_REST_Server                  $server  Server instance.
     * @param WP_REST_Request<array<mixed>>   $request Request used to generate the response.
     * @param string                          $url The request URL.
     */
    public function __construct(
        public readonly mixed $result,
        public readonly WP_REST_Server $server,
        public readonly WP_REST_Request $request,
        public readonly string $url,
    ) {}

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array {
        return [
            'result'  => $this->result,
            'server'  => $this->server,
            'request' => $this->request,
            'url'     => $this->url,
        ];
    }
}
