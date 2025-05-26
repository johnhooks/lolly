<?php

declare(strict_types=1);

namespace Dozuki\Listeners;

use Dozuki\Lib\Contracts\Whitelist;
use Dozuki\ValueObjects\WpRestApiContext;
use Dozuki\Psr\Log\LoggerInterface;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * LogOnRestApiRequest class.
 *
 * Handles logging of REST API requests and responses, capturing relevant
 * context for debugging and analysis.
 *
 * @package Dozuki
 */
class LogOnRestApiRequest {
    public function __construct(
        private readonly Whitelist\Config $config,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Fires after the REST API response is processed.
     *
     * @param WP_REST_Response|WP_Error|mixed $result  Result to send to the client. Usually a `WP_REST_Response`.
     * @param WP_REST_Server                  $server  Server instance.
     * @param WP_REST_Request                 $request Request used to generate the response.
     *
     * @return WP_REST_Response|WP_Error|mixed
     */
    public function handle( $result, $server, $request ) {
        // Perhaps the URL should be built from a combination of the WP_REST_Request data
        // like path, in combination with `get_rest_url`.
        $url = \Dozuki\Lib::get_full_request_url();

        if ( $this->config->is_whitelist_enabled() ) {
            if ( ! $this->config->is_http_url_whitelisted( $url ) ) {
                return $result;
            }
        }

        $log_context = [
            'wp_rest_api' => new WpRestApiContext(
                $result,
                $server,
                $request,
                $url,
            ),
        ];

        if (
            $result instanceof WP_Error ||
            ( $result instanceof WP_REST_Response && $result->is_error() )
        ) {
            $this->logger->error( 'HTTP REST request.', $log_context );
        } else {
            $this->logger->info( 'HTTP REST request.', $log_context );
        }

        return $result;
    }
}
