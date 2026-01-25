<?php

declare(strict_types=1);

namespace Lolly\Listeners;

use Lolly\Lib;
use Lolly\Lib\Contracts\Whitelist;
use Lolly\ValueObjects\WpHttpClientContext;
use Lolly\ValueObjects\WpRestApiContext;
use Lolly\Psr\Log\LoggerInterface;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * HttpListener class.
 *
 * Handles logging of HTTP events in WordPress.
 *
 * @package Lolly
 */
class HttpListener {

    public function __construct(
        private readonly Whitelist\Config $whitelist_config,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Handle the http_api_debug action for outbound HTTP client requests.
     *
     * @param array<mixed>|WP_Error $response     HTTP response or WP_Error object.
     * @param string                $context      Context under which the hook is fired.
     * @param string                $transport    HTTP transport used.
     * @param array<mixed>          $request_args HTTP request arguments.
     * @param string                $url          The request URL.
     */
    public function on_client_request( $response, $context, $transport, $request_args, $url ): void {
        if ( str_contains( $url, 'doing_wp_cron' ) ) {
            return;
        }

        // Ignore loopback requests.
        if ( $url === trailingslashit( get_site_url() ) ) {
            return;
        }

        if ( $this->whitelist_config->is_whitelist_enabled() ) {
            if ( ! $this->whitelist_config->is_http_url_whitelisted( $url ) ) {
                return;
            }
        }

        $log_context = [
            'wp_http_client' => new WpHttpClientContext(
                $response,
                $context,
                $transport,
                $request_args,
                $url,
            ),
        ];

        if ( $response instanceof WP_Error ) {
            $this->logger->error( 'HTTP client request.', $log_context );
        } else {
            $this->logger->info( 'HTTP client request.', $log_context );
        }
    }

    /**
     * Handle the rest_post_dispatch filter for incoming REST API requests.
     *
     * @param WP_REST_Response|WP_Error|mixed $result  Result to send to the client.
     * @param WP_REST_Server                  $server  Server instance.
     * @param WP_REST_Request<array<mixed>>   $request Request used to generate the response.
     *
     * @return WP_REST_Response|WP_Error|mixed
     */
    public function on_rest_request( $result, WP_REST_Server $server, WP_REST_Request $request ) {
        $url = Lib::get_full_request_url();

        if ( $this->whitelist_config->is_whitelist_enabled() ) {
            if ( ! $this->whitelist_config->is_http_url_whitelisted( $url ) ) {
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
