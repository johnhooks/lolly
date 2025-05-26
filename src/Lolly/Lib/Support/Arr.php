<?php

declare(strict_types=1);

namespace Lolly\Lib\Support;

use ArrayAccess;
use Closure;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/*
 * Some methods copied from Laravel Framework [Arr](https://github.com/laravel/framework/blob/17786ca25fa9080f0b4f03af7517e9fc72fa0b4a/src/Illuminate/Collections/Arr.php).
 *
 * The MIT License (MIT)
 *
 * Copyright (c) Taylor Otwell
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of
 * this software and associated documentation files (the "Software"), to deal in
 * the Software without restriction, including without limitation the rights to
 * use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies
 * of the Software, and to permit persons to whom the Software is furnished to do
 * so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

/**
 * Class Arr.
 *
 * Implement array functions not in PHP.
 *
 * @package Lolly
 */
class Arr {

    /**
     * Determine whether the given value is array-accessible.
     *
     * @param mixed $value
     *
     * @return bool
     */
    public static function accessible( $value ): bool {
        return is_array( $value ) || $value instanceof ArrayAccess;
    }

    /**
     * Flatten a multi-dimensional associative array with dots.
     *
     * @param iterable $array
     * @param string   $prepend
     *
     * @return array
     */
    public static function dot( iterable $array, string $prepend = '' ): array {
        $results = [];

        foreach ( $array as $key => $value ) {
            if ( is_array( $value ) && ! empty( $value ) ) {
                $results = array_merge( $results, static::dot( $value, $prepend . $key . '.' ) );
            } else {
                $results[ $prepend . $key ] = $value;
            }
        }

        return $results;
    }

    /**
     * Determine if the given key exists in the provided array.
     *
     * @param ArrayAccess|array $array
     * @param string|int|float  $key
     *
     * @return bool
     */
    public static function exists( ArrayAccess|array $array, string|int|float $key ): bool {
        if ( $array instanceof ArrayAccess ) {
            return $array->offsetExists( $key );
        }

        if ( is_float( $key ) ) {
            $key = (string) $key;
        }

        return array_key_exists( $key, $array );
    }

    /**
     * Find an item in a list using a predicate function.
     *
     * Note: PHP 8.4 provides `array_find`, eventually this function can be removed.
     *
     * @template T
     *
     * @param list<T>  $array
     * @param callable $predicate
     *
     * @return T|null
     */
    public static function find( array $array, callable $predicate ): mixed {
        foreach ( $array as $value ) {
            if ( call_user_func( $predicate, $value ) ) {
                return $value;
            }
        }

        return null;
    }

    /**
     * Get an item from an array using "dot" notation.
     *
     * @param ArrayAccess|array $array
     * @param string|int|null   $key
     * @param mixed             $default
     *
     * @return mixed
     */
    public static function get(
        ArrayAccess|array $array,
        string|int|null $key,
        mixed $default = null
    ): mixed {
        if ( ! static::accessible( $array ) ) {
            return static::value( $default );
        }

        if ( is_null( $key ) ) {
            return $array;
        }

        if ( static::exists( $array, $key ) ) {
            return $array[ $key ];
        }

        if ( ! str_contains( $key, '.' ) ) {
            return $array[ $key ] ?? static::value( $default );
        }

        foreach ( explode( '.', $key ) as $segment ) {
            if ( static::accessible( $array ) && static::exists( $array, $segment ) ) {
                $array = $array[ $segment ];
            } else {
                return static::value( $default );
            }
        }

        return $array;
    }

    /**
     * Whether a list contains a match to a predicate function.
     *
     * @param array    $array
     * @param callable $predicate
     *
     * @return bool
     */
    public static function contains( array $array, callable $predicate ): bool {
        return (bool) static::find( $array, $predicate );
    }

    /**
     * Whether a list contains a match to a predicate function.
     *
     * @param array    $array
     * @param callable $predicate
     *
     * @return bool
     */
    public static function doesnt_contain( array $array, callable $predicate ): bool {
        return ! static::find( $array, $predicate );
    }

    /**
     * Return the default value of the given value.
     *
     * @param mixed $value
     * @param mixed ...$args
     *
     * @return mixed
     */
    public static function value( mixed $value, ...$args ): mixed {
        return $value instanceof Closure ? $value( ...$args ) : $value;
    }
}
