<?php

declare(strict_types=1);

namespace Dozuki\Listeners;

use Dozuki\ValueObjects\WpHttpClientContext;
use Dozuki\Psr\Log\LoggerInterface;
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
 * @package Dozuki
 */
class LogOnHttpClientRequest {

    public function __construct(
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

        $log_context = [
            // @todo Should this be here?
            'url'            => $url,
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
