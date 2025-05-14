<?php declare(strict_types=1);

namespace Dozuki\Processors;

use Dozuki\Config\Config;
use Dozuki\Processors\Concerns\FormatsHeaders;
use Dozuki\ValueObjects\WpRestApiContext;
use Dozuki\GuzzleHttp\Psr7\Request;
use Dozuki\GuzzleHttp\Psr7\Response;
use Dozuki\Monolog\LogRecord;
use Dozuki\Monolog\Processor\ProcessorInterface;

use WP_Error;
use WP_REST_Response;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Hints the request is from itself
// Headers
// "referer":["http://ok-wp-logger.test/wp-admin/post.php?post=2&action=edit"]
// "origin":["http://ok-wp-logger.test"]

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

            $context['http_request'] ??= new Request(
                $value->request->get_method(),
                wp_guess_url(),
                $value->request->get_headers(),
                $value->request->get_body(),
            );

            if ( $value->result instanceof WP_REST_Response ) {
                $body = $value->result->jsonSerialize();

                if ( is_array( $body ) ) {
                    $body = json_encode( $body );

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
