<?php

declare(strict_types=1);

namespace Dozuki\Lib\Services\Redactors\HttpMessage;

use Dozuki\Lib\Contracts\Redactors;
use Dozuki\Lib\Enums\HttpRedactionType;
use Dozuki\Lib\Support\Str;
use Dozuki\GuzzleHttp\Psr7\Utils;
use Dozuki\Lib\ValueObjects\Http\RedactionItem;
use Dozuki\Psr\Http\Message\MessageInterface;
use Dozuki\Psr\Http\Message\RequestInterface;
use Dozuki\Psr\Http\Message\ResponseInterface;
use Dozuki\Psr\Http\Message\UriInterface;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// @todo need to add the concept of removing properties, there is a difference
// between redacting an dropping. Redacting keeps the keys so you know they
// were there. Removing discards key and value. It would be great if I could
// figure out a way to do both in one place. Otherwise we loop over everything
// twice, and we wouldn't want to decode/encode more than necessary. Also we
// want to make sure to remove properties before attempting to redact them,
// otherwise we will just undo what was previously done.

/**
 * @todo I need to rewrite this all to use the new redaction config.
 */

/**
 * DefaultRedactor class.
 */
final class DefaultRedactor implements Redactors\HttpMessage {
    /**
     * @param Redactors\Config $config
     */
    public function __construct(
        protected readonly Redactors\Config $config,
    ) {}

    /**
     * @inheritDoc
     */
    public function redact(
        UriInterface|string $url,
        MessageInterface $message
    ): MessageInterface {
        if ( is_string( $url ) ) {
            $url = Utils::uriFor( $url );
        }

        // @todo Not a huge fan of this, but it's better than passing multiple variables around
        // Perhaps we need a redactor that contains the state and is called with `__invoke`.
        $context = new Context( $url, $this->config->get_http_redactions( $url ) );

        if ( $message instanceof RequestInterface ) {
            return $this->redact_request( $context, $message );
        } elseif ( $message instanceof ResponseInterface ) {
            return $this->redact_response( $context, $message );
        }

        return $message;
    }


    private function redact_url(
        Context $context,
        UriInterface $url
    ): UriInterface {
        $query_redactions = $this->get_redactions( $context, HttpRedactionType::Query );

        if ( count( $query_redactions ) === 0 ) {
            return $url;
        }

        return $url->withQuery( $this->redact_query_params( $url->getQuery(), $query_redactions ) );
    }

    /**
     * @template T RequestInterface|ResponseInterface
     *
     * @param Context $context
     * @param T       $message
     *
     * @return T
     */
    private function redact_headers( Context $context, $message ) {
        $header_redactions = $this->get_redactions( $context, HttpRedactionType::Header );

        // @todo How can redact all be handled better?
        if ( ! is_array( $header_redactions ) ) {
            foreach ( $message->getHeaders() as $header => $_ ) {
                $message = $message->withHeader( $header, '[redacted]' );
            }

            return $message;
        }

        foreach ( $header_redactions as $header ) {
            $message = $message->withHeader( $header, '[redacted]' );
        }

        return $message;
    }


    /**
     * @template T RequestInterface|ResponseInterface
     *
     * @param Context $context
     * @param T       $message
     *
     * @return T
     */
    private function redact_body( Context $context, $message ) {
        $body_redactions = $message instanceof RequestInterface
            ? $this->get_redactions( $context, HttpRedactionType::Request )
            : $this->get_redactions( $context, HttpRedactionType::Response );

        $content_type = $message->getHeaderLine( 'Content-Type' );

        if ( $body_redactions === true && $this->content_type_is_url_encoded( $content_type ) ) {
            $body = 'redacted=1';
        } elseif ( $body_redactions === true ) {
            $body = '"[redacted]"';
        } else {
            $body = $this->redact_body_content( $message->getBody()->getContents(), $body_redactions, $content_type );
        }

        return $message->withBody( Utils::streamFor( $body ) );
    }

    private function redact_request( Context $context, RequestInterface $request ): RequestInterface {
        $request = $request->withUri( $this->redact_url( $context, $request->getUri() ), true );
        $request = $this->redact_headers( $context, $request );
        return $this->redact_body( $context, $request );
    }

    private function redact_response( Context $context, ResponseInterface $response ): ResponseInterface {
        $response = $this->redact_headers( $context, $response );
        return $this->redact_body( $context, $response );
    }

    /**
     * @param Context           $context
     * @param HttpRedactionType $type
     *
     * @return RedactionItem[] A list of keys to redact.
     */
    private function get_redactions( Context $context, HttpRedactionType $type ) {
        /** @var RedactionItem[] $result */
        $result = [];

        /** @var ?RedactionItem $all */
        $all = null;

        foreach ( $context->redactions as $redaction ) {
            if (
                $redaction->type === HttpRedactionType::Always ||
                $redaction->type === $type
            ) {
                if ( $redaction->value === '*' ) {
                        $all = $redaction;
                    if ( $all->should_remove ) {
                        return [ $all ];
                    }
                }

                $result[] = $redaction;
            }
        }

        if ( $all !== null ) {
            return [ $all ];
        }

        return $result;
    }

    private function content_type_is_json(
        string $content_type
    ): bool {
        return Str::contains( $content_type, [ '/json', '+json' ] );
    }

    private function content_type_is_url_encoded(
        string $content_type
    ): bool {
        return Str::contains( $content_type, [ '/x-www-form-urlencoded' ] );
    }

    /**
     * Redact data from the HTTP message body
     *
     * If encode/decode fails, the entire JSON data structure is redacted.
     *
     * @param string                  $content
     * @param list<RedactionItem> $redactions
     * @param string                  $content_type
     *
     * @return string The redacted data.
     */
    private function redact_body_content(
        string $content,
        array $redactions,
        string $content_type
    ): string {
        if ( $this->content_type_is_json( $content_type ) ) {
            return $this->redact_json_content( $content, $redactions );
        } elseif ( $this->content_type_is_url_encoded( $content_type ) ) {
            return $this->redact_query_params( $content, $redactions );
        }

        return '[redacted]';
    }

    /**
     * Decode, walk and redact specific keys from JSON content.
     *
     * If encode/decode fail, the entire JSON data structure is redacted.
     *
     * @param string                  $content
     * @param list<RedactionItem> $redactions
     *
     * @return string The re-encoded and redacted data.
     */
    private function redact_json_content(
        string $content,
        array $redactions
    ): string {
        $data = json_decode( $content, true );

        if ( json_last_error() !== JSON_ERROR_NONE ) {
            return '"JSON decode error: ' . json_last_error_msg() . '"';
        }

        $redacted = $this->redact_keys( $data, $redactions );
        $encoded  = json_encode( $redacted );

        if ( json_last_error() !== JSON_ERROR_NONE ) {
            return '"JSON encode error: ' . json_last_error_msg() . '"';
        }

        return $encoded;
    }

    /**
     * Recursively redact keys from an associative array.
     *
     * @param array                   $data
     * @param list<RedactionItem> $redactions
     * @param bool                    $is_query
     *
     * @return array
     */
    private function redact_keys(
        array $data,
        array $redactions,
        bool $is_query = false
    ): array {
        foreach ( $data as $key => $value ) {
            if ( in_array( $key, $redactions, true ) ) {
                $data[ $key ] = $is_query ? 'redacted' : '[redacted]';
                continue;
            }

            if ( is_array( $value ) && ! array_is_list( $value ) ) {
                $data[ $key ] = $this->redact_keys( $value, $redactions, $is_query );
            }
        }

        return $data;
    }

    /**
     * @param string                  $query
     * @param list<RedactionItem> $redactions
     * @return string
     */
    private function redact_query_params(
        string $query,
        array $redactions
    ): string {
        $parsed = [];
        parse_str( $query, $parsed );
        $redacted = $this->redact_keys( $parsed, $redactions, true );
        return http_build_query( $redacted );
    }
}
