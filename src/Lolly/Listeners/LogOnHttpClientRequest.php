<?php

declare(strict_types=1);

namespace Lolly\Listeners;

use Lolly\Lib\Contracts\Whitelist;
use Lolly\ValueObjects\WpHttpClientContext;
use Lolly\Psr\Log\LoggerInterface;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/*
 * @todo Investigate converting to use the Requests hooks.
 *   - fsockopen.after_send
 *   - requests.before_parse
 */


/**
 * LogOnHttpClientRequest class.
 *
 * Handle logging of WP HTTP client requests and responses.
 *
 * @package Lolly
 */
class LogOnHttpClientRequest {

    public function __construct(
        private readonly Whitelist\Config $config,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Fires after an HTTP API response is received and before the response is returned.
     *
     * @param array<mixed>|WP_Error $response     HTTP response or WP_Error object.
     * @param string                $context      Context under which the hook is fired.
     * @param string                $transport    HTTP transport used.
     * @param array<mixed>          $request_args HTTP request arguments.
     * @param string                $url          The request URL.
     */
    public function handle( $response, $context, $transport, $request_args, $url ): void {
        if ( str_contains( $url, 'doing_wp_cron' ) ) {
            return;
        }

        // Ignore loopback requests.
        if ( $url === trailingslashit( get_site_url() ) ) {
            return;
        }

        if ( $this->config->is_whitelist_enabled() ) {
            if ( ! $this->config->is_http_url_whitelisted( $url ) ) {
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
}
