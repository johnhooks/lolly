<?php

declare(strict_types=1);

namespace Lolly\Lib\Formatters;

use Lolly\Monolog\LogRecord;
use Lolly\Monolog\Formatter\NormalizerFormatter;
use RuntimeException;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/*
 * This file was inspired by Monolog [LogstashFormatter](https://github.com/Seldaek/monolog/blob/548eeb3f1e313d943712b9fab0dc2e865a5a7bfc/src/Monolog/Formatter/LogstashFormatter.php)
 *
 * Original author Tim Mower <timothy.mower@gmail.com>
 *
 * Copyright (c) 2011-2020 Jordi Boggiano
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

/**
 * EcsLogStashFormatter class.
 * Serializes a log message to Logstash Event Format
 *
 * @see    https://www.elastic.co/products/logstash
 * @see    https://github.com/elastic/logstash/blob/master/logstash-core/src/main/java/org/logstash/Event.java
 *
 * @package Lolly
 */
class EcsLogStashFormatter extends NormalizerFormatter {
    /**
     * @var string The name of the system for the Logstash log message, used to
     *             fill the @source field, defaults to the hostname of the machine.
     */
    protected readonly string $system_name;

    /**
     * @param string      $application_name The application name for the Logstash log message, used to fill the @type field.
     * @param string|null $system_name      The name of the system for the Logstash log message, used to fill the @source field, defaults to the hostname of the machine.
     * @param string      $dataset_prefix  The prefix for the event "dataset" property, prepended to log channel name, defaults to `app`.
     * @param string      $extra_key        The key for extra keys inside logstash "fields", defaults to `extra`.
     * @param string      $context_key      The key for context keys inside logstash "fields", defaults to `context`.
     * @param string      $context_prefix   The prefix for additional context inside logstash "fields", defaults to `ctxt_`.
     *
     * @throws RuntimeException If the function json_encode does not exist.
     */
    public function __construct(
        protected readonly string $application_name,
        ?string $system_name = null,
        protected readonly string $dataset_prefix = 'app',
        protected readonly string $extra_key = 'extra',
        protected readonly string $context_key = 'context',
        protected readonly string $context_prefix = 'ctxt_',
    ) {
        // Logstash requires a ISO 8601 format date with optional millisecond precision.
        parent::__construct( 'Y-m-d\TH:i:s.uP' );

        $this->system_name = $system_name === null ? (string) gethostname() : $system_name;
    }

    /**
     * @inheritDoc
     */
    public function format( LogRecord $record ): string {
        /** @var array<string,mixed> $record_data */
        $record_data = parent::format( $record );

        $message = [
            '@timestamp' => $record_data['datetime'],
            '@version'   => 1,
            'host'       => $this->system_name,
        ];

        if ( isset( $record_data['message'] ) ) {
            $message['message'] = $record_data['message'];
        }
        if ( isset( $record_data['channel'] ) ) {
            $message['type']    = $record_data['channel'];
            $message['channel'] = $record_data['channel'];
        }
        if ( isset( $record_data['level_name'] ) ) {
            $message['level'] = $record_data['level_name'];
        }
        if ( isset( $record_data['level'] ) ) {
            $message['monolog_level'] = $record_data['level'];
        }
        if ( '' !== $this->application_name ) {
            $message['type'] = $this->application_name;
        }
        if ( count( $record_data['extra'] ) > 0 ) {
            $message[ $this->extra_key ] = $record_data['extra'];
        }
        if ( count( $record_data['context'] ) > 0 ) {
            foreach ( $record_data['context'] as $key => $val ) {
                $message[ $this->context_prefix . $key ] = $val;
            }
        }

        // Customization of original LogStashFormatter.

        $message['event'] = [
            'dataset'  => $this->dataset_prefix . $record_data['channel'],
            'kind'     => 'event',
            'module'   => 'lolly',
            'severity' => $record_data['level'],
        ];

        if ( isset( $record_data['context']['$event'] ) ) {
            $message['event'] = array_merge( $message['event'], $record_data['context']['$event'] );
        }

        foreach ( $record['context'] as $key => $value ) {
            if ( $this->should_remove_context( $record_data, $key, $value ) ) {
                unset( $message[ $this->context_prefix . $key ] );
            }
        }

        if ( isset( $message['extra']['http']['request']['body']['content'] ) ) {
            $message['extra']['http']['request']['body']['content'] = $this->maybe_truncate(
                $message['extra']['http']['request']['body']['content'],
            );
        }

        if ( isset( $message['extra']['http']['response']['body']['content'] ) ) {
            $message['extra']['http']['response']['body']['content'] = $this->maybe_truncate(
                $message['extra']['http']['response']['body']['content'],
            );
        }

        unset(
            $message['channel'],
            $message['type'],
            $message[ $this->context_prefix . '$event' ],
        );

        return $this->toJson( $message ) . "\n";
    }

    /**
     * Checks if the context should be removed from the LogStash message.
     *
     * A context is removed if it is duplicated by an extra field.
     *
     * @param array<string, mixed> $record
     * @param string               $key
     * @param mixed                $value
     */
    protected function should_remove_context( array $record, string $key, mixed $value ): bool {
        if (
            is_array( $value ) &&
            isset( $record['extra'][ $key ] ) &&
            $record['extra'][ $key ] === $value
        ) {
            return true;
        }

        if ( 'exception' === $key && isset( $record['extra']['error'] ) ) {
            return true;
        }

        if (
            is_array( $value ) &&
            isset( $record['extra']['http']['request'], $value['http'] ) &&
            $record['extra']['http']['request'] === $value['http']
        ) {
            return true;
        }

        if (
            is_array( $value ) &&
            isset( $record['extra']['http']['response'], $value['http'] ) &&
            $record['extra']['http']['response'] === $value['http']
        ) {
            return true;
        }

        if ( is_array( $value ) &&
            isset( $record['extra']['user'], $value['id'], $value['username'] ) &&
            $record['extra']['user'] === $value
        ) {
            return true;
        }

        return false;
    }

    private function maybe_truncate( string $content, int $truncate_size = 1500 ): string {
        if ( mb_strlen( $content ) > $truncate_size ) {
            return mb_substr( $content, 0, $truncate_size ) . ' (truncated...)';
        }

        return $content;
    }
}
