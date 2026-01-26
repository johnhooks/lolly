<?php

declare(strict_types=1);

namespace Tests\Stubs;

use Lolly\Psr\Log\LoggerInterface;

/**
 * Spy logger for testing.
 *
 * Tracks method calls for assertions in tests.
 */
class SpyLogger implements LoggerInterface {

    /**
     * @var array<int, array{level: string, message: string, context: array<string, mixed>}>
     */
    public array $logs = [];

    public function emergency( $message, array $context = [] ): void {
        $this->log( 'emergency', $message, $context );
    }

    public function alert( $message, array $context = [] ): void {
        $this->log( 'alert', $message, $context );
    }

    public function critical( $message, array $context = [] ): void {
        $this->log( 'critical', $message, $context );
    }

    public function error( $message, array $context = [] ): void {
        $this->log( 'error', $message, $context );
    }

    public function warning( $message, array $context = [] ): void {
        $this->log( 'warning', $message, $context );
    }

    public function notice( $message, array $context = [] ): void {
        $this->log( 'notice', $message, $context );
    }

    public function info( $message, array $context = [] ): void {
        $this->log( 'info', $message, $context );
    }

    public function debug( $message, array $context = [] ): void {
        $this->log( 'debug', $message, $context );
    }

    /**
     * @param mixed                $level   Log level.
     * @param mixed                $message Log message.
     * @param array<string, mixed> $context Log context.
     */
    public function log( $level, $message, array $context = [] ): void {
        $this->logs[] = [
            'level'   => (string) $level,
            'message' => (string) $message,
            'context' => $context,
        ];
    }

    /**
     * Get the count of logs at a specific level.
     *
     * @param string $level The log level.
     *
     * @return int The count.
     */
    public function count_level( string $level ): int {
        return count(
            array_filter(
                $this->logs,
                fn( $log ) => $log['level'] === $level
            )
        );
    }

    /**
     * Reset the logs.
     */
    public function reset(): void {
        $this->logs = [];
    }
}
