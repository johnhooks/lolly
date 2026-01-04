<?php

declare(strict_types=1);

namespace Lolly\Lib\Processors;

use ArrayAccess;
use Lolly\Lib\Support\Arr;
use JsonSerializable;
use Lolly\Monolog\LogRecord;
use Lolly\Monolog\Processor\ProcessorInterface;
use Lolly\Monolog\Utils;
use Throwable;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * PsrLogMessageProcessor class.
 *
 * Processes a record's message according to PSR-3 rules
 *
 * It replaces {foo} with the value from $context['foo']
 *
 * Modified for more useful interpolations.
 *
 * @author Jordi Boggiano <j.boggiano@seld.be>
 *
 * @package Lolly
 */
class PsrLogMessageProcessor implements ProcessorInterface {

    /**
     * @param int $max_depth Max depth to descend a dot notion path.
     */
    public function __construct(
        protected readonly int $max_depth = 3,
    ) {
    }

    public function __invoke( LogRecord $record ): LogRecord {
        if ( ! str_contains( $record->message, '{' ) ) {
            return $record;
        }

        $message = preg_replace_callback(
            '/{([a-zA-Z0-9_.]+)}/',
            function ( $matches ) use ( $record ) {
                $key = $matches[1];

                if ( array_key_exists( $key, $record->context ) ) {
                    return $this->interpolate( $record->context[ $key ] );
                }

                if ( ! str_contains( $key, '.' ) ) {
                    return '{' . $key . '}';
                }

                return $this->walk( $key, $record->context );
            },
            $record['message']
        );

        return $record->with( message: $message );
    }

    private function walk( string $path, mixed $context, int $depth = 0 ): string {
        if ( $depth > $this->max_depth ) {
            return '[max depth exceeded]';
        }

        $pair = explode( '.', ltrim( $path ), 2 );

        if ( ! isset( $pair[0] ) ) {
            return '[undefined]';
        }

        $key  = rtrim( $pair[0] );
        $next = $pair[1] ?? null;

        if ( is_object( $context ) ) {
            if ( property_exists( $context, $key ) ) {
                /** @var mixed $value */
                $value = $context->{$key}; // @phpstan-ignore-line Variable property access.

                if ( $next === null ) {
                    return $this->interpolate( $value );
                }

                return $this->walk( $next, $value, $depth + 1 );
            }
        }

        if ( is_array( $context ) || $context instanceof ArrayAccess ) {
            if ( array_key_exists( $key, $context ) ) {
                $value = $context[ $key ];

                if ( $next === null ) {
                    return $this->interpolate( $value );
                }

                return $this->walk( $next, $value, $depth + 1 );
            } else {
                return '[undefined]';
            }
        }

        return '[undefined]';
    }

    private function interpolate( mixed $value ): string {
        if ( is_object( $value ) ) {
            if ( $value instanceof Throwable ) {
                return $value->getMessage();
            }

            if ( method_exists( $value, '__toString' ) ) {
                return (string) $value;
            }

            if ( $value instanceof JsonSerializable ) {
                $value = $value->jsonSerialize();
            }
        }

        if ( $value === null ) {
            return '[null]';
        }

        if ( $value === '' ) {
            return '[empty string]';
        }

        if ( $value === true ) {
            return '[true]';
        }

        if ( $value === false ) {
            return '[false]';
        }

        if ( is_scalar( $value ) ) {
            return (string) $value;
        }

        if ( is_object( $value ) ) {
            return '[object ' . Utils::getClass( $value ) . ']';
        }

        if ( is_iterable( $value ) ) {
            if ( array_is_list( $value ) && Arr::doesnt_contain( $value, fn( $v ) => ! is_scalar( $v ) ) ) {
                $value = array_map( $this->interpolate( ... ), $value );
            } else {
                $result = [];

                foreach ( $value as $k => $v ) {
                    $result[] = $k . ' => ' . $this->interpolate( $v );
                }

                $value = $result;
            }

            return '[' . implode( ', ', $value ) . ']';
        }

        return '[' . gettype( $value ) . ']';
    }
}
