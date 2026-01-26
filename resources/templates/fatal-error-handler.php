<?php
/**
 * Plugin Name: Lolly Fatal Error Handler
 * Description: Drop-in fatal error handler for Lolly Log plugin.
 * Version: 1.0.0
 *
 * This file is a WordPress drop-in that provides enhanced fatal error logging.
 * It captures detailed error information including backtraces and extension detection.
 *
 * @package Lolly
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Global flag to indicate the drop-in has already logged the fatal error.
 * This prevents duplicate logging from the shutdown hook.
 *
 * @var bool
 */
$GLOBALS['lolly_fatal_error_logged'] = false;

/**
 * Lolly Fatal Error Handler.
 *
 * Extends the WordPress fatal error handler to provide enhanced logging
 * capabilities with the Lolly Log plugin.
 */
class Lolly_Fatal_Error_Handler extends WP_Fatal_Error_Handler {

    /**
     * Runs the shutdown handler.
     *
     * This method is invoked when a fatal error occurs. It logs the error
     * using Lolly if available, then falls back to WordPress default behavior.
     */
    public function handle(): void {
        $error = $this->detect_error();

        if ( $error === null ) {
            return;
        }

        // Attempt to log with Lolly if available.
        if ( $this->log_with_lolly( $error ) ) {
            $GLOBALS['lolly_fatal_error_logged'] = true;
        }

        // Call parent handler for recovery mode and error display.
        parent::handle();
    }

    /**
     * Log the fatal error using Lolly.
     *
     * @param array{type: int, message: string, file: string, line: int} $error Error details from error_get_last().
     *
     * @return bool True if logged successfully, false otherwise.
     */
    private function log_with_lolly( array $error ): bool {
        // Check if Lolly is available.
        if ( ! function_exists( 'lolly' ) ) {
            return false;
        }

        try {
            $context = $this->build_error_context( $error );
            lolly()->critical( $this->format_error_message( $error ), $context );
            return true;
        } catch ( Throwable $e ) {
            // Silently fail if logging throws an exception.
            return false;
        }
    }

    /**
     * Build the error context for logging.
     *
     * @param array{type: int, message: string, file: string, line: int} $error Error details.
     *
     * @return array<string, mixed> The error context.
     */
    private function build_error_context( array $error ): array {
        $context = [
            'error' => [
                'type'    => $this->get_error_type_name( $error['type'] ),
                'code'    => $error['type'],
                'message' => $error['message'],
                'file'    => $error['file'],
                'line'    => $error['line'],
            ],
        ];

        // Add backtrace if available (requires Xdebug - standard debug_backtrace doesn't work for fatal errors).
        $backtrace = $this->get_backtrace();
        if ( ! empty( $backtrace ) ) {
            $context['error']['stack_trace'] = $backtrace;
        }

        // Add extension detection (may not work if fatal occurred before WP fully loaded).
        $extension = $this->detect_extension( $error );
        if ( $extension !== null ) {
            $context['error']['extension'] = $extension;
        }

        // Add recovery mode status.
        $context['error']['recovery_mode'] = $this->is_recovery_mode_active();

        // Add WordPress context if available (functions may not exist during early boot fatal).
        if ( function_exists( 'get_bloginfo' ) ) {
            $context['wordpress'] = [
                'version'   => get_bloginfo( 'version' ),
                'multisite' => function_exists( 'is_multisite' ) && is_multisite(),
            ];
        }

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

        return $context;
    }

    /**
     * Format the error message for logging.
     *
     * @param array{type: int, message: string, file: string, line: int} $error Error details.
     *
     * @return string Formatted error message.
     */
    private function format_error_message( array $error ): string {
        return sprintf(
            'PHP Fatal Error: %s in %s on line %d',
            $error['message'],
            $error['file'],
            $error['line']
        );
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

    /**
     * Get the backtrace if available.
     *
     * @return array<int, array{file?: string, line?: int, function?: string, class?: string, type?: string}>
     */
    private function get_backtrace(): array {
        // Check if Xdebug's extended info is available.
        if ( function_exists( 'xdebug_get_function_stack' ) ) {
            // phpcs:ignore PHPCompatibility.FunctionUse.NewFunctions.xdebug_get_function_stackFound -- Optional Xdebug function.
            $stack = xdebug_get_function_stack();
            if ( is_array( $stack ) ) {
                // Reverse to get most recent call first.
                return array_reverse( $stack );
            }
        }

        return [];
    }

    /**
     * Detect the WordPress extension (plugin or theme) responsible for the error.
     *
     * Note: This may not work if the fatal error occurred before WordPress fully loaded.
     *
     * @param array{type: int, message: string, file: string, line: int} $error Error details.
     *
     * @return array{type: string, slug: string, name?: string}|null Extension info or null if not detected.
     */
    private function detect_extension( array $error ): ?array {
        // These functions/constants may not exist during early boot.
        if ( ! function_exists( 'wp_normalize_path' ) || ! defined( 'WP_PLUGIN_DIR' ) ) {
            return null;
        }

        $file = wp_normalize_path( $error['file'] );

        // Check if error is in a plugin.
        $plugins_dir = wp_normalize_path( WP_PLUGIN_DIR );
        if ( str_starts_with( $file, $plugins_dir . '/' ) ) {
            $relative = substr( $file, strlen( $plugins_dir ) + 1 );
            $parts    = explode( '/', $relative );
            $slug     = $parts[0] ?? '';

            if ( ! empty( $slug ) ) {
                return [
                    'type' => 'plugin',
                    'slug' => $slug,
                    'name' => $this->get_plugin_name( $slug ),
                ];
            }
        }

        // Check if error is in mu-plugins.
        if ( defined( 'WPMU_PLUGIN_DIR' ) ) {
            $mu_plugins_dir = wp_normalize_path( WPMU_PLUGIN_DIR );
            if ( str_starts_with( $file, $mu_plugins_dir . '/' ) ) {
                $relative = substr( $file, strlen( $mu_plugins_dir ) + 1 );
                $parts    = explode( '/', $relative );
                $slug     = $parts[0] ?? '';

                if ( ! empty( $slug ) ) {
                    return [
                        'type' => 'mu-plugin',
                        'slug' => $slug,
                    ];
                }
            }
        }

        // Check if error is in a theme.
        if ( function_exists( 'get_theme_root' ) ) {
            $themes_dir = wp_normalize_path( get_theme_root() );
            if ( str_starts_with( $file, $themes_dir . '/' ) ) {
                $relative = substr( $file, strlen( $themes_dir ) + 1 );
                $parts    = explode( '/', $relative );
                $slug     = $parts[0] ?? '';

                if ( ! empty( $slug ) ) {
                    $name = null;
                    if ( function_exists( 'wp_get_theme' ) ) {
                        $theme = wp_get_theme( $slug );
                        $name  = $theme->exists() ? $theme->get( 'Name' ) : null;
                    }
                    return [
                        'type' => 'theme',
                        'slug' => $slug,
                        'name' => $name,
                    ];
                }
            }
        }

        // Check if error is in WordPress core.
        if ( defined( 'ABSPATH' ) && defined( 'WP_CONTENT_DIR' ) ) {
            $abspath = wp_normalize_path( ABSPATH );
            if ( str_starts_with( $file, $abspath ) ) {
                // Exclude wp-content directory.
                $wp_content = wp_normalize_path( WP_CONTENT_DIR );
                if ( ! str_starts_with( $file, $wp_content ) ) {
                    return [
                        'type' => 'core',
                        'slug' => 'wordpress',
                    ];
                }
            }
        }

        return null;
    }

    /**
     * Get the plugin name from its slug.
     *
     * @param string $slug The plugin folder name.
     *
     * @return string|null The plugin name or null if not found.
     */
    private function get_plugin_name( string $slug ): ?string {
        // The plugin.php file may not be loadable during early fatal errors.
        if ( ! defined( 'ABSPATH' ) ) {
            return null;
        }

        $plugin_file = ABSPATH . 'wp-admin/includes/plugin.php';
        if ( ! function_exists( 'get_plugins' ) ) {
            if ( ! file_exists( $plugin_file ) ) {
                return null;
            }
            require_once $plugin_file;
        }

        // get_plugins may still not exist if require failed.
        if ( ! function_exists( 'get_plugins' ) ) {
            return null;
        }

        $plugins = get_plugins();

        foreach ( $plugins as $plugin_file => $plugin_data ) {
            if ( str_starts_with( $plugin_file, $slug . '/' ) || $plugin_file === $slug . '.php' ) {
                return $plugin_data['Name'] ?? null;
            }
        }

        return null;
    }

    /**
     * Check if WordPress recovery mode is active.
     *
     * @return bool True if recovery mode is active.
     */
    private function is_recovery_mode_active(): bool {
        if ( ! function_exists( 'wp_is_recovery_mode' ) ) {
            return false;
        }

        return wp_is_recovery_mode();
    }
}

// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- This is a drop-in that must override the global handler.
$GLOBALS['wp_fatal_error_handler'] = new Lolly_Fatal_Error_Handler();
