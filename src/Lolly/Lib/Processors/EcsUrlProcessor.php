<?php

declare(strict_types=1);

namespace Lolly\Lib\Processors;

use Lolly\Monolog\LogRecord;
use Lolly\Monolog\Processor\ProcessorInterface;
use Lolly\Psr\Http\Message\UriInterface;
use Lolly\GuzzleHttp\Psr7\Uri;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * EcsUrlProcessor class.
 *
 * Adds the "url" field for ECS/ELK.
 *
 * @link https://www.elastic.co/docs/reference/ecs/ecs-url
 *
 * @package Lolly
 */
class EcsUrlProcessor implements ProcessorInterface {
    public function __invoke( LogRecord $record ): LogRecord {
        $context = $record->context;
        $extra   = $record->extra;

        /** @var array<string,mixed>|null $formatted */
        $formatted = null;

        foreach ( $context as $key => $value ) {
            if ( $value instanceof UriInterface ) {
                $url = $value;
            } elseif ( $key === 'url' && is_string( $value ) ) {
                $url = new Uri( $value );
            } else {
                continue;
            }

            $formatted       = $this->format_url( $url );
            $context[ $key ] = $formatted;

            if ( $key === 'url' ) {
                $extra['url'] = $context['url'];
            }
        }

        if ( ! isset( $extra['url'] ) && $formatted !== null ) {
            $extra['url'] = $formatted;
        }

        return $record->with( context: $context, extra: $extra );
    }

    /**
     * @return array<string, mixed>
     */
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
}
