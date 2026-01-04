<?php

declare(strict_types=1);

namespace Lolly\Lib\Services\Redactors\HttpMessage;

use Lolly\Lib\Contracts\Redactors;
use Lolly\Lib\Enums\HttpRedactionType;
use Lolly\Lib\Support\Str;
use Lolly\GuzzleHttp\Psr7\Utils;
use Lolly\Lib\ValueObjects\Http\RedactionItem;
use Lolly\Psr\Http\Message\MessageInterface;
use Lolly\Psr\Http\Message\RequestInterface;
use Lolly\Psr\Http\Message\ResponseInterface;
use Lolly\Psr\Http\Message\UriInterface;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * DefaultRedactor class.
 *
 * The default implementation of the `Redactors\HttpMessage` interface.
 *
 * @package Lolly
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
        MessageInterface $message,
        array $redactions = [],
    ): MessageInterface {
        if ( is_string( $url ) ) {
            $url = Utils::uriFor( $url );
        }

        $redactions = $this->config->is_http_redactions_enabled()
            ? array_merge( $this->config->get_http_redactions( $url ), $redactions )
            : $redactions;

        $context = new Context( $url, $redactions );

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

        if ( count( $query_redactions ) === 1 && $query_redactions[0]->value === '*' ) {
            if ( $query_redactions[0]->should_remove ) {
                return $url->withQuery( '' );
            } else {
                return $url->withQuery( 'redacted=1' );
            }
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
        $redactions = $this->get_redactions( $context, HttpRedactionType::Header );

        if ( count( $redactions ) === 0 ) {
            return $message;
        }

        if ( count( $redactions ) === 1 && $redactions[0]->value === '*' ) {
            $headers       = $message->getHeaders();
            $should_remove = $redactions[0]->should_remove;

            foreach ( $headers as $header => $_ ) {
                if ( $should_remove ) {
                    $message = $message->withoutHeader( $header );
                } else {
                    $message = $message->withHeader( $header, 'redacted' );
                }
            }

            return $message;
        }

        foreach ( $redactions as $redaction ) {
            $headers = $message->getHeaders();

            foreach ( $headers as $header => $_ ) {
                if ( strtolower( $redaction->value ) === strtolower( $header ) ) {
                    if ( $redaction->should_remove ) {
                        $message = $message->withoutHeader( $header );
                    } else {
                        $message = $message->withHeader( $header, 'redacted' );
                    }
                }
            }
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
        $redactions = $message instanceof RequestInterface
            ? $this->get_redactions( $context, HttpRedactionType::Request )
            : $this->get_redactions( $context, HttpRedactionType::Response );

        if ( count( $redactions ) === 0 ) {
            return $message;
        }

        $content_type = $message->getHeaderLine( 'Content-Type' );

        if ( count( $redactions ) === 1 && $redactions[0]->value === '*' ) {
            if ( $redactions[0]->should_remove ) {
                return $message->withBody( Utils::streamFor() );
            } elseif ( $this->content_type_is_json( $content_type ) ) {
                return $message->withBody( Utils::streamFor( '"redacted"' ) );
            } elseif ( $this->content_type_is_url_encoded( $content_type ) ) {
                return $message->withBody( Utils::streamFor( 'redacted=1' ) );
            } else {
                return $message->withBody( Utils::streamFor( 'redacted' ) );
            }
        }

        $body = $this->redact_body_content( $message->getBody()->getContents(), $redactions, $content_type );

        return $message->withBody( Utils::streamFor( $body ) );
    }

    private function redact_request( Context $context, RequestInterface $request ): RequestInterface {
        $request = $request->withUri( $this->redact_url( $context, $request->getUri() ), true );
        $request = $this->redact_headers( $context, $request );

        if ( strtolower( $request->getMethod() ) !== 'get' && $request->getBody()->getSize() > 0 ) {
            return $this->redact_body( $context, $request );
        }

        return $request;
    }

    private function redact_response( Context $context, ResponseInterface $response ): ResponseInterface {
        $response = $this->redact_headers( $context, $response );

        if ( $response->getBody()->getSize() > 0 ) {
            return $this->redact_body( $context, $response );
        }

        return $response;
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
     * @param string              $content
     * @param list<RedactionItem> $redactions
     * @param string              $content_type
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

        return $content;
    }

    /**
     * Decode, walk and redact specific keys from JSON content.
     *
     * If encode/decode fail, the entire JSON data structure is redacted.
     *
     * @param string              $content
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
     * @param array<string, mixed> $data
     * @param list<RedactionItem>  $redactions
     * @param bool                 $is_query
     *
     * @return array<string, mixed> The redacted data.
     */
    private function redact_keys(
        array $data,
        array $redactions,
        bool $is_query = false
    ): array {
        foreach ( $data as $key => $value ) {
            foreach ( $redactions as $redaction ) {
                if ( $redaction->value === $key ) {
                    if ( $redaction->should_remove ) {
                        unset( $data[ $key ] );
                    } else {
                        $data[ $key ] = $is_query ? 'redacted' : 'redacted';
                    }
                } elseif ( is_array( $value ) && ! array_is_list( $value ) ) {
                        $data[ $key ] = $this->redact_keys( $value, $redactions, $is_query );
                }
            }
        }

        return $data;
    }

    /**
     * @param string              $query
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
