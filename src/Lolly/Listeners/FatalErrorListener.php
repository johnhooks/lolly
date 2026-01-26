<?php

declare(strict_types=1);

namespace Lolly\Listeners;

use Lolly\Psr\Log\LoggerInterface;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * FatalErrorListener class.
 *
 * Handles logging of fatal PHP errors via shutdown hook.
 * This provides basic error logging when the drop-in handler is not installed.
 *
 * @package Lolly
 */
class FatalErrorListener {

    /**
     * Fatal error types to capture.
     *
     * @var int[]
     */
    private const FATAL_TYPES = [
        E_ERROR,
        E_PARSE,
        E_CORE_ERROR,
        E_COMPILE_ERROR,
        E_USER_ERROR,
        E_RECOVERABLE_ERROR,
    ];

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Handle the shutdown event.
     *
     * Called via register_shutdown_function. Checks for fatal errors
     * and logs them if not already handled by the drop-in.
     */
    public function on_shutdown(): void {
        // Check if the drop-in already logged this error.
        if ( isset( $GLOBALS['lolly_fatal_error_logged'] ) && $GLOBALS['lolly_fatal_error_logged'] === true ) {
            return;
        }

        $error = error_get_last();

        if ( $error === null ) {
            return;
        }

        if ( ! in_array( $error['type'], self::FATAL_TYPES, true ) ) {
            return;
        }

        $this->log_error( $error );

        // Set the flag to prevent duplicate logging if something else checks.
        $GLOBALS['lolly_fatal_error_logged'] = true;
    }

    /**
     * Log the fatal error.
     *
     * @param array{type: int, message: string, file: string, line: int} $error Error details from error_get_last().
     */
    private function log_error( array $error ): void {
        $message = sprintf(
            'PHP Fatal Error: %s in %s on line %d',
            $error['message'],
            $error['file'],
            $error['line']
        );

        $context = [
            'error' => [
                'type'    => $this->get_error_type_name( $error['type'] ),
                'code'    => $error['type'],
                'message' => $error['message'],
                'file'    => $error['file'],
                'line'    => $error['line'],
            ],
        ];

        // Add request context if available.
        if ( isset( $_SERVER['REQUEST_URI'] ) ) {
            $context['http'] = [
                'request' => [
                    'method' => isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : 'unknown',
                    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- URI sanitization would corrupt valid URIs.
                    'url'    => isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '',
                ],
            ];
        }

        $this->logger->critical( $message, $context );
    }

    /**
     * Get the human-readable name for an error type.
     *
     * @param int $type The PHP error type constant.
     *
     * @return string The error type name.
     */
    private function get_error_type_name( int $type ): string {
        $types = [
            E_ERROR             => 'E_ERROR',
            E_PARSE             => 'E_PARSE',
            E_CORE_ERROR        => 'E_CORE_ERROR',
            E_COMPILE_ERROR     => 'E_COMPILE_ERROR',
            E_USER_ERROR        => 'E_USER_ERROR',
            E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
        ];

        return $types[ $type ] ?? 'UNKNOWN';
    }
}
