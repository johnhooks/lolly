<?php declare(strict_types=1);

namespace Dozuki\Processors;

use Dozuki\Monolog\LogRecord;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class UrlProcessor {
    private const URL_MAP = [
        'host'     => 'domain',
        'path'     => 'path',
        'query'    => 'query',
        'fragment' => 'fragment',
        'port'     => 'port',
        'scheme'   => 'scheme',
        'user'     => 'username',
    ];

    // Perhaps this is on a trait, that the other processors can use.
    public function __invoke( LogRecord $record ): LogRecord {
        if ( ! isset( $record->context['url'] ) ) {
            return $record;
        }

        $context = $record->context;
        $extra   = $record->extra;

        $url = $this->format_url( $context['url'] );

        $extra['url'] = $url;
        unset( $context['url'] );

        return $record->with( context: $context, extra: $extra );
    }

    private function format_url( string $url ): array {
        $parts = parse_url( $url );

        $data = [
            'original' => $url,
        ];

        if ( is_array( $parts ) ) {
            foreach ( self::URL_MAP as $php => $ecs ) {
                if ( isset( $parts[ $php ] ) ) {
                    $data[ $ecs ] = $parts[ $php ];
                }
            }
        }

        if ( isset( $data['path'] ) ) {
            /** @var string $ext */
            $ext = pathinfo( $data['path'], PATHINFO_EXTENSION );

            if ( strlen( $ext ) !== 0 && ! is_numeric( $ext ) ) {
                $data['extension'] = $ext;
            }
        }

        return $data;
    }
}
