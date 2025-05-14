<?php

declare(strict_types=1);

namespace Dozuki\Lib\Processors;

use Dozuki\Monolog\LogRecord;
use Dozuki\Monolog\Processor\ProcessorInterface;
use Dozuki\Psr\Http\Message\{MessageInterface, RequestInterface, ResponseInterface, UriInterface};

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class EcsHttpMessageProcessor
 *
 * The headers handling is not formally defined in the spec as of yet,
 * it is implemented as described in the linked PR.
 *
 * @link https://www.elastic.co/guide/en/ecs/current/ecs-http.html
 * @link https://www.elastic.co/guide/en/ecs/current/ecs-url.html
 * @link https://github.com/elastic/ecs/pull/554
 */
class EcsHttpMessageProcessor implements ProcessorInterface {
    private const UNREADABLE = 'Body stream is not seekable/readable.';

    public function __invoke( LogRecord $record ) {
        $context = $record->context;
        $extra   = $record->extra;
        $http    = [];

        foreach ( $context as $key => $value ) {
            if ( $value instanceof RequestInterface ) {
                $request = $this->format_request( $value );
                $url     = $this->format_url( $value->getUri() );

                if ( ! isset( $http['request'] ) ) {
                    $http['version'] ??= $value->getProtocolVersion();
                    $http['request']   = $request;
                    $extra['url']      = $url;

                    unset( $context[ $key ] );
                } else {
                    $context[ $key ]        = $request;
                    $context[ $key ]['url'] = $url;
                }

                continue;
            }

            if ( $value instanceof ResponseInterface ) {
                $response = $this->format_response( $value );

                if ( ! isset( $http['response'] ) ) {
                    $http['response'] = $response;

                    unset( $context[ $key ] );
                } else {
                    $context[ $key ] = $response;
                }
            }
        }

        if ( count( $http ) > 0 ) {
            $extra['http'] = $http;
        }

        return $record->with( context: $context, extra: $extra );
    }

    private function format_request( RequestInterface $request ): array {
        $result = [
            'method'  => strtolower( $request->getMethod() ),
            'headers' => $request->getHeaders(),
        ];

        $body = $this->format_body( $request );

        $length = strlen( $body );

        if ( $length > 0 ) {
            $result['body'] = [
                'content' => $body,
            ];

            $bytes = $request->getBody()->getSize();

            $result['body']['bytes'] = $bytes !== null ? $bytes : $length;
        }

        return $result;
    }

    private function format_response( ResponseInterface $response ): array {
        $result = [
            'status_code' => $response->getStatusCode(),
            'headers'     => $response->getHeaders(),
        ];

        $body = $this->format_body( $response );

        $length = strlen( $body );

        if ( $length > 0 ) {
            $result['body'] = [
                'content' => $body,
            ];

            $bytes = $response->getBody()->getSize();

            $result['body']['bytes'] = $bytes !== null ? $bytes : $length;
        }

        return $result;
    }

    private function format_url( UriInterface $uri ): array {
        $result = [
            'original' => (string) $uri,
            'domain'   => $uri->getHost(),
            'path'     => $uri->getPath(),
            'query'    => $uri->getQuery(),
            'fragment' => $uri->getFragment(),
            'port'     => $uri->getPort(),
            'scheme'   => $uri->getScheme(),
            'username' => $uri->getUserInfo(),
        ];

        if ( $result['path'] !== '' ) {
            $ext = pathinfo( $result['path'], PATHINFO_EXTENSION );

            if ( strlen( $ext ) > 0 ) {
                $result['extension'] = $ext;
            }
        }

        foreach ( $result as $key => $value ) {
            if ( $value === null || $value === '' ) {
                unset( $result[ $key ] );
            }
        }

        return $result;
    }

    private function format_body( MessageInterface $message ): string {
        $stream = $message->getBody();

        if ( $stream->isSeekable() === false || $stream->isReadable() === false ) {
            return self::UNREADABLE;
        }

        $body = $stream->__toString();
        $stream->rewind();

        return $body;
    }
}
