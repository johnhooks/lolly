<?php

declare(strict_types=1);

namespace Lolly\Dropin;

use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Manages the fatal-error-handler.php drop-in file.
 *
 * Handles installation, uninstallation, and status checking of the
 * Lolly fatal error handler drop-in.
 */
class DropinManager {

    /**
     * The drop-in filename.
     */
    public const DROPIN_FILENAME = 'fatal-error-handler.php';

    /**
     * Version header identifier in the drop-in file.
     */
    private const VERSION_HEADER = 'Lolly Fatal Error Handler';

    /**
     * Get the status of the drop-in.
     *
     * @return array{installed: bool, is_lolly: bool, version: string|null, writable: bool}|WP_Error
     */
    public function get_status(): array|WP_Error {
        $dropin_path  = $this->get_dropin_path();
        $is_installed = file_exists( $dropin_path );
        $is_lolly     = $is_installed && $this->is_ours();
        $version      = $is_lolly ? $this->get_dropin_version() : null;
        $is_writable  = $this->is_wp_content_writable();

        return [
            'installed' => $is_installed,
            'is_lolly'  => $is_lolly,
            'version'   => $version,
            'writable'  => $is_writable,
        ];
    }

    /**
     * Install the drop-in file.
     *
     * @return bool|WP_Error True on success, WP_Error on failure.
     */
    public function install(): bool|WP_Error {
        $template_path = $this->get_template_path();

        if ( ! file_exists( $template_path ) ) {
            return new WP_Error(
                'lolly_dropin_template_missing',
                __( 'The drop-in template file is missing from the plugin.', 'lolly' ),
                [ 'status' => 500 ]
            );
        }

        if ( ! $this->is_wp_content_writable() ) {
            return new WP_Error(
                'lolly_dropin_not_writable',
                __( 'The wp-content directory is not writable.', 'lolly' ),
                [ 'status' => 500 ]
            );
        }

        $dropin_path = $this->get_dropin_path();

        // Check if a third-party drop-in exists.
        if ( file_exists( $dropin_path ) && ! $this->is_ours() ) {
            return new WP_Error(
                'lolly_dropin_exists_third_party',
                __( 'A fatal error handler from another plugin is already installed.', 'lolly' ),
                [ 'status' => 409 ]
            );
        }

        // Copy the template to wp-content.
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_copy
        $copied = copy( $template_path, $dropin_path );

        if ( ! $copied ) {
            return new WP_Error(
                'lolly_dropin_copy_failed',
                __( 'Failed to copy the drop-in file to wp-content.', 'lolly' ),
                [ 'status' => 500 ]
            );
        }

        return true;
    }

    /**
     * Uninstall the drop-in file.
     *
     * Only removes the drop-in if it belongs to Lolly.
     *
     * @return bool|WP_Error True on success, WP_Error on failure.
     */
    public function uninstall(): bool|WP_Error {
        $dropin_path = $this->get_dropin_path();

        if ( ! file_exists( $dropin_path ) ) {
            // Not installed, nothing to do.
            return true;
        }

        if ( ! $this->is_ours() ) {
            return new WP_Error(
                'lolly_dropin_exists_third_party',
                __( 'Cannot uninstall: the drop-in belongs to another plugin.', 'lolly' ),
                [ 'status' => 409 ]
            );
        }

        if ( ! $this->is_wp_content_writable() ) {
            return new WP_Error(
                'lolly_dropin_not_writable',
                __( 'The wp-content directory is not writable.', 'lolly' ),
                [ 'status' => 500 ]
            );
        }

        // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
        $deleted = unlink( $dropin_path );

        if ( ! $deleted ) {
            return new WP_Error(
                'lolly_dropin_delete_failed',
                __( 'Failed to delete the drop-in file.', 'lolly' ),
                [ 'status' => 500 ]
            );
        }

        return true;
    }

    /**
     * Check if the drop-in is installed.
     *
     * @return bool
     */
    public function is_installed(): bool {
        return file_exists( $this->get_dropin_path() );
    }

    /**
     * Check if the installed drop-in belongs to Lolly.
     *
     * @return bool
     */
    public function is_ours(): bool {
        $dropin_path = $this->get_dropin_path();

        if ( ! file_exists( $dropin_path ) ) {
            return false;
        }

        $file_data = get_file_data(
            $dropin_path,
            [ 'Name' => 'Plugin Name' ]
        );

        return isset( $file_data['Name'] ) && $file_data['Name'] === self::VERSION_HEADER;
    }

    /**
     * Get the version of the installed Lolly drop-in.
     *
     * @return string|null The version, or null if not installed or not ours.
     */
    public function get_dropin_version(): ?string {
        $dropin_path = $this->get_dropin_path();

        if ( ! file_exists( $dropin_path ) ) {
            return null;
        }

        $file_data = get_file_data(
            $dropin_path,
            [ 'Version' => 'Version' ]
        );

        return isset( $file_data['Version'] ) && $file_data['Version'] !== '' ? $file_data['Version'] : null;
    }

    /**
     * Get the path to the drop-in file in wp-content.
     *
     * @return string
     */
    public function get_dropin_path(): string {
        return WP_CONTENT_DIR . '/' . self::DROPIN_FILENAME;
    }

    /**
     * Get the path to the drop-in template in the plugin.
     *
     * @return string
     */
    public function get_template_path(): string {
        return plugin_dir_path( LOLLY_PLUGIN_FILE ) . 'resources/templates/' . self::DROPIN_FILENAME;
    }

    /**
     * Check if the wp-content directory is writable.
     *
     * @return bool
     */
    private function is_wp_content_writable(): bool {
        return wp_is_writable( WP_CONTENT_DIR );
    }
}
