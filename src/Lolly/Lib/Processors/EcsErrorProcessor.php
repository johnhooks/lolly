<?php

declare(strict_types=1);

namespace Lolly\Lib\Processors;

use Lolly\Monolog\LogRecord;
use Lolly\Monolog\Processor\ProcessorInterface;
use Throwable;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * EcsErrorProcessor class.
 *
 * Adds the "error" field for ECS/ELK.
 *
 * @link https://www.elastic.co/guide/en/ecs/current/ecs-error
 *
 * @package Lolly
 */
class EcsErrorProcessor implements ProcessorInterface {
    public function __invoke( LogRecord $record ): LogRecord {
        $e = $record->context['exception'] ?? null;

        if ( ! $e instanceof Throwable ) {
            return $record;
        }

        $record->extra['error'] = [
            'code'        => (string) $e->getCode(),
            'message'     => $e->getMessage(),
            'stack_trace' => [
                'text' => $this->get_exception_trace_as_string( $e ),
            ],
            'type'        => get_class( $e ),
        ];

        return $record;
    }

    public function get_exception_trace_as_string( Throwable $exception ): string {
        $trace = '';
        $count = 0;

        foreach ( $exception->getTrace() as $frame ) {
            $args = '';
            if ( isset( $frame['args'] ) ) {
                $args = [];
                foreach ( $frame['args'] as $arg ) {
                    if ( is_string( $arg ) ) {
                        $args[] = "'" . $arg . "'";
                    } elseif ( is_array( $arg ) ) {
                        $args[] = 'Array';
                    } elseif ( is_null( $arg ) ) {
                        $args[] = 'null';
                    } elseif ( is_bool( $arg ) ) {
                        $args[] = ( $arg ) ? 'true' : 'false';
                    } elseif ( is_object( $arg ) ) {
                        $args[] = get_class( $arg );
                    } elseif ( is_resource( $arg ) ) {
                        $args[] = get_resource_type( $arg );
                    } elseif ( is_scalar( $arg ) ) {
                        $args[] = $arg;
                    } else {
                        $args[] = gettype( $arg );
                    }
                }
                $args = join( ', ', $args );
            }

            $trace .= sprintf(
                "#%s %s(%s): %s%s%s(%s)\n",
                $count,
                $frame['file'] ?? '',
                $frame['line'] ?? '',
                $frame['class'] ?? '',
                $frame['type'] ?? '',
                // "->" or "::"
                $frame['function'],
                $args
            );

            ++$count;
        }

        return $trace;
    }
}
