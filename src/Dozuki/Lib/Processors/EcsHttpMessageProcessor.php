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

                if ( ! isset( $http['request'] ) ) {
                    $http['version'] ??= $value->getProtocolVersion();
                    $http['request']   = $request;

                    unset( $context[ $key ] );
                } else {
                    $context[ $key ] = $request;
                }

                continue;
            }

            if ( $value instanceof ResponseInterface ) {
                $response = $this->format_response( $value );

                if ( ! isset( $http['response'] ) ) {
                    $http['version'] ??= $value->getProtocolVersion();
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

            // @todo How can we get the bytes from before redaction?
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
