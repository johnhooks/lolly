<?php

declare(strict_types=1);

namespace Dozuki\Processors;

use Dozuki\ValueObjects\WpRestApiContext;
use Dozuki\GuzzleHttp\Psr7\Request;
use Dozuki\GuzzleHttp\Psr7\Response;
use Dozuki\Monolog\LogRecord;
use Dozuki\Monolog\Processor\ProcessorInterface;
use Dozuki\GuzzleHttp\Psr7\Uri;
use WP_Error;
use WP_REST_Response;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/*
 * Hints the request is from itself.
 * Headers:
 *   "referer":["http://ok-wp-logger.test/wp-admin/post.php?post=2&action=edit"]
 *   "origin": ["http://ok-wp-logger.test"]
 */

/**
 * WpRestApiProcessor class.
 *
 * Transform `WpRestApiContext` into Psr7 HTTP messages.
 */
class WpRestApiProcessor implements ProcessorInterface {
    public function __invoke( LogRecord $record ): LogRecord {
        $context = $record->context;
        $extra   = $record->extra;

        foreach ( $context as $key => $value ) {
            if ( ! ( $value instanceof WpRestApiContext ) ) {
                continue;
            }

            $raw_url = $value->url;

            $context['url']          ??= new Uri( $raw_url );
            $context['http_request'] ??= new Request(
                $value->request->get_method(),
                $raw_url,
                $value->request->get_headers(),
                $value->request->get_body(),
            );

            if ( $value->result instanceof WP_REST_Response ) {
                // Note: The return of `jsonSerialized` is type-hinted as `mixed`.
                // It should be possible to pass it directly to `json_encode`,
                // though let's perform a few checks.
                $raw_body = $value->result->jsonSerialize();

                /** @var string|null $body */
                $body = null;

                if ( $raw_body instanceof \stdClass ) {
                    $body = get_object_vars( $raw_body );
                }

                if ( is_array( $raw_body ) ) {
                    $body = json_encode( $raw_body );

                    if ( json_last_error() !== JSON_ERROR_NONE || $body === false ) {
                        $body = '"JSON encode error: ' . json_last_error_msg() . '"';
                    }
                }

                $context['http_response'] ??= new Response(
                    $value->result->get_status(),
                    $value->result->get_headers(),
                    $body,
                );

                if ( $value->result->is_error() ) {
                    if ( ! $context['wp_error'] ) {
                        $context['wp_error'] = $value->result->as_error();
                    }
                }
            }

            if ( $value->result instanceof WP_Error ) {
                $context['wp_error'] ??= $value->result;
            }

            unset( $context[ $key ] );
        }

        return $record->with( context: $context, extra: $extra );
    }
}
