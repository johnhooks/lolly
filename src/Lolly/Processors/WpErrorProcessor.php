<?php

declare(strict_types=1);

namespace Lolly\Processors;

use Lolly\Monolog\LogRecord;
use Lolly\Monolog\Processor\ProcessorInterface;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * WpErrorProcessor class.
 *
 * Transforms a `WP_Error` into an "error" field for ECS/ELK.
 *
 * @link https://www.elastic.co/guide/en/ecs/current/ecs-error
 *
 * @package Lolly
 */
class WpErrorProcessor implements ProcessorInterface {
    public function __invoke( LogRecord $record ): LogRecord {
        $context = $record->context;
        $extra   = $record->extra;

        foreach ( $context as $_ => $value ) {
            if ( ! is_wp_error( $value ) ) {
                continue;
            }

            // @todo Capture all the error data.
            if ( ! isset( $extra['error'] ) ) {
                $extra['error'] = [
                    'code'    => $value->get_error_code(),
                    'message' => $value->get_error_message(),
                    'type'    => 'WP_Error',
                ];

                // @todo The error fields doesn't provide the structure to
                // capture the entire WP_Error object. For now leave the value
                // in the context.
                // unset( $context[ $key ] );
            }
        }

        return $record->with( context: $context, extra: $extra );
    }
}
