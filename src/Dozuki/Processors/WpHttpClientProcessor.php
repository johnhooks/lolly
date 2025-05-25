<?php

declare(strict_types=1);

namespace Dozuki\Processors;

use Dozuki\GuzzleHttp\Psr7\Uri;
use Dozuki\Monolog\Processor\ProcessorInterface;
use Dozuki\ValueObjects\WpHttpClientContext;
use Dozuki\GuzzleHttp\Psr7\Request;
use Dozuki\GuzzleHttp\Psr7\Response;
use Dozuki\Monolog\LogRecord;
use WP_Error;
use WpOrg\Requests\Requests;
use WpOrg\Requests\Utility\CaseInsensitiveDictionary;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * WpHttpClientProcessor class.
 *
 * Transform `WpHttpClientContext` into Psr7 HTTP messages.
 */
class WpHttpClientProcessor implements ProcessorInterface {
    public function __invoke( LogRecord $record ): LogRecord {
        $context = $record->context;
        $extra   = $record->extra;

        foreach ( $context as $key => $value ) {
            if ( ! ( $value instanceof WpHttpClientContext ) ) {
                continue;
            }

            // @todo Need to research other transports.
            if ( $value->transport !== Requests::class ) {
                continue;
            }

            // Ignore loopback requests.
            if ( $value->url === trailingslashit( get_site_url() ) ) {
                continue;
            }

            $raw_body = $value->request_args['body'] ?? null;
            $body     = null;

            if ( is_array( $raw_body ) ) {
                $body = json_encode( $raw_body );

                if ( json_last_error() !== JSON_ERROR_NONE || $body === false ) {
                    $body = '"JSON encode error: ' . json_last_error_msg() . '"';
                }
            }

            $raw_headers = $value->request_args['headers'] ?? null;
            $headers     = [];

            if ( $raw_headers instanceof CaseInsensitiveDictionary ) {
                $headers = $raw_headers->getAll();
            } elseif ( is_array( $raw_headers ) ) {
                $headers = $raw_headers;
            }

            $context['url']          ??= new Uri( $value->url );
            $context['http_request'] ??= new Request(
                $value->request_args['method'] ?? 'undefined',
                $value->url,
                $headers,
                $body,
            );

            if ( is_array( $value->response ) ) {
                $status       = isset( $value->response['response']['code'] ) && is_int( $value->response['response']['code'] )
                    ? $value->response['response']['code']
                    : 500;
                $content_type = $value->response['headers']['Content-Type'] ?? '';
                $include_body = ! str_contains( $content_type, 'text/html' );

                $context['http_response'] ??= new Response(
                    $status,
                    $value->response['headers']->getAll(),
                    $include_body && isset( $value->response['body'] )
                        ? $value->response['body']
                        : null,
                );
            }

            if ( $value->response instanceof WP_Error ) {
                $context['wp_error'] ??= $value->response;
            }

            unset( $context[ $key ] );
        }

        return $record->with( context: $context, extra: $extra );
    }
}
