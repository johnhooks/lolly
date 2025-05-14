<?php

declare(strict_types=1);

namespace Dozuki\Lib\Processors;

use Dozuki\Lib\Support\Str;
use Dozuki\Monolog\LogRecord;
use Dozuki\Monolog\Processor\ProcessorInterface;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class EcsHttpRedactionProcessor.
 *
 * It redacts HTTP values matching keys from $context['redactions']
 *
 * REQUIREMENT: Must follow after the `EscHttpMessageProcessor` in the Processor order.
 *
 * $context['redactions']['all'] - If set and truthy, redact everything: query string, request and response bodies.
 * $context['redactions']['headers'] - Array of keys to redact from the headers.
 * $context['redactions']['query'] - Array of keys to redact from the query string, the original is completely redacted.
 * $context['redactions']['request'] - Array of keys to redact from the request body.
 * $context['redactions']['response'] - Array of keys to redact from the response body.
 *
 * Redacting body content only works with content-type JSON or URL encoded, for other
 * content types the entire body is entirely redacted, if any redactions for the
 * respective $context['redactions'] key provide are provided.
 */
class EcsHttpRedactionProcessor implements ProcessorInterface {

    public const JSON_DECODE_ERROR = '[json decode error]';

    public const JSON_ENCODE_ERROR = '[json encode error]';

    /**
     * @param string $redacted_replacement The text used to replace redacted content, defaults to `xxxxxx` which is safe in a query string.
     */
    public function __construct(
        protected readonly string $redacted_replacement = 'xxxxxx'
    ) {}

    public function __invoke( LogRecord $record ) {
        $redactions = $record->context['redactions'] ?? null;

        if ( $redactions === null || count( $redactions ) === 0 ) {
            return $record;
        }

        if ( isset( $redactions['all'] ) && $redactions['all'] === true ) {
            return $this->redact_everything( $record );
        }

        return $this->redact_targeted( $record, $redactions );
    }

    private function redact_everything( LogRecord $record ): LogRecord {
        $context = $record->context;
        $extra   = $record->extra;

        if ( isset( $extra['http']['request']['body']['content'] ) ) {
            $extra['http']['request']['body']['content'] = $this->redacted_replacement;
        }

        if ( isset( $extra['http']['response']['body']['content'] ) ) {
            $extra['http']['response']['body']['content'] = $this->redacted_replacement;
        }

        if ( isset( $extra['url']['query'] ) ) {
            $extra['url']['original'] = $this->redacted_replacement;
            $extra['url']['query']    = $this->redacted_replacement;
        }

        unset( $context['redactions'] );

        return $record->with( context: $context, extra: $extra );
    }

    private function redact_targeted( LogRecord $record, array $redactions ): LogRecord {
        $context = $record->context;
        $extra   = $record->extra;

        if ( isset( $redactions['request'] ) && isset( $extra['http']['request']['body']['content'] ) ) {
            $extra['http']['request']['body']['content'] = $this->redact_body_content(
                $extra['http']['request']['body']['content'],
                $redactions['request'],
                content_type: $extra['http']['request']['content-type'] ?? '',
            );
        }

        if ( isset( $redactions['query'] ) && isset( $extra['url']['query'] ) ) {
            if ( isset( $extra['url']['original'] ) ) {
                $extra['url']['original'] = $this->redacted_replacement;
            }

            $extra['url']['query'] = $this->redact_query_params(
                $extra['url']['query'],
                $redactions['query'],
            );
        }

        if ( isset( $redactions['response'] ) && isset( $extra['http']['response']['body']['content'] ) ) {
            $extra['http']['response']['body']['content'] = $this->redact_body_content(
                $extra['http']['response']['body']['content'],
                $redactions['response'],
                content_type: $extra['http']['response']['content-type'] ?? ''
            );
        }

        // Remove the redactions from the context.
        unset( $context['redactions'] );

        return $record->with( context: $context, extra: $extra );
    }

    private function content_type_is_json( string $content_type ): bool {
        return Str::contains( $content_type, [ '/json', '+json' ] );
    }

    private function content_type_is_url_encoded( string $content_type ): bool {
        return Str::contains( $content_type, [ '/x-www-form-urlencoded' ] );
    }

    /**
     * Redact data from HTTP message body
     *
     * If encode/decode fail, the entire JSON data structure is redacted.
     *
     * @param string       $content
     * @param list<string> $redactions
     * @param string       $content_type
     *
     * @return string The redacted data.
     */
    private function redact_body_content( string $content, array $redactions, string $content_type ): string {
        if ( $this->content_type_is_json( $content_type ) ) {
            return $this->redact_json_content( $content, $redactions );
        } elseif ( $this->content_type_is_url_encoded( $content_type ) ) {
            return $this->redact_query_params( $content, $redactions );
        }

        return $this->redacted_replacement;
    }

    /**
     * Decode, walk and redact specific keys from JSON content.
     *
     * If encode/decode fail, the entire JSON data structure is redacted.
     *
     * @param string       $content
     * @param list<string> $redactions
     *
     * @return string The re-encoded and redacted data.
     */
    private function redact_json_content( string $content, array $redactions ): string {
        $data = json_decode( $content, true );

        if ( json_last_error() !== JSON_ERROR_NONE ) {
            return self::JSON_DECODE_ERROR;
        }

        $redacted = $this->redact_keys( $data, $redactions );
        $encoded  = json_encode( $redacted );

        if ( json_last_error() !== JSON_ERROR_NONE ) {
            return self::JSON_ENCODE_ERROR;
        }

        return $encoded;
    }

    /**
     * Recursively redact keys from an associative array.
     *
     * @param array        $data
     * @param list<string> $redactions
     *
     * @return array
     */
    private function redact_keys( array $data, array $redactions ): array {
        foreach ( $data as $key => $value ) {
            if ( in_array( $key, $redactions, true ) ) {
                $data[ $key ] = $this->redacted_replacement;
                continue;
            }

            if ( is_array( $value ) && ! array_is_list( $value ) ) {
                $data[ $key ] = $this->redact_keys( $value, $redactions );
            }
        }

        return $data;
    }

    private function redact_query_params( string $query, array $redactions ): string {
        $parsed = [];
        parse_str( $query, $parsed );
        $redacted = $this->redact_keys( $parsed, $redactions );
        return http_build_query( $redacted );
    }
}
