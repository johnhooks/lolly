<?php

declare(strict_types=1);

namespace Lolly\Lib\Support;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/*
 * Some methods copied from Laravel Framework [Str](https://github.com/laravel/framework/blob/17786ca25fa9080f0b4f03af7517e9fc72fa0b4a/src/Illuminate/Support/Str.php).
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
 * Class Str.
 *
 * Implement string functions not in PHP.
 *
 * @package Lolly
 */
class Str {

    /**
     * Determine if a given string contains a given substring.
     *
     * @param  string                  $haystack
     * @param  string|iterable<string> $needles
     * @param  bool                    $ignore_case
     *
     * @return bool
     */
    public static function contains(
        string $haystack,
        mixed $needles,
        bool $ignore_case = false
    ): bool {
        if ( $ignore_case ) {
            $haystack = mb_strtolower( $haystack );
        }

        if ( ! is_iterable( $needles ) ) {
            $needles = (array) $needles;
        }

        foreach ( $needles as $needle ) {
            if ( $ignore_case ) {
                $needle = mb_strtolower( $needle );
            }

            if ( $needle !== '' && str_contains( $haystack, $needle ) ) {
                return true;
            }
        }

        return false;
    }
}
