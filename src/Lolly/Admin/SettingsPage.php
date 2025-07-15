<?php

declare(strict_types=1);

namespace Lolly\Admin;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * SettingsPage class.
 *
 * Handles the admin settings page for Lolly.
 *
 * @package Lolly
 */
class SettingsPage {
    public const MENU_SLUG = 'lolly-settings';

    /**
     * Add admin menu page
     */
    public function add_admin_menu() {
        add_options_page(
            __( 'Lolly Log Settings', 'lolly' ),
            __( 'Lolly Log', 'lolly' ),
            'manage_options',
            self::MENU_SLUG,
            [ $this, 'render_settings_page' ]
        );
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_assets( $hook ) {
        if ( 'settings_page_lolly-settings' !== $hook ) {
            return;
        }

        $asset_path = plugin_dir_path( LOLLY_PLUGIN_FILE ) . 'build/admin.asset.php';

        if ( file_exists( $asset_path ) ) {
            $asset = require $asset_path;
        }

        if ( ! isset( $asset ) ) {
            lolly()->error(
                '[Lolly] Missing settings page asset file.',
                [
                    'asset_path' => $asset_path,
                ]
            );

            return;
        }

        wp_enqueue_style(
            'lolly-admin-styles',
            plugin_dir_url( LOLLY_PLUGIN_FILE ) . 'build/style-admin.css',
            [ 'wp-components' ],
            $asset['version']
        );

        wp_enqueue_script(
            'lolly-admin-scripts',
            plugin_dir_url( LOLLY_PLUGIN_FILE ) . 'build/admin.js',
            $asset['dependencies'],
            $asset['version'],
            true
        );

        $schema_path = plugin_dir_path( LOLLY_PLUGIN_FILE ) . 'resources/schemas/http-logging-config.json';
        $schema_data = null;

        if ( file_exists( $schema_path ) ) {
            $schema_json = file_get_contents( $schema_path );
            if ( $schema_json !== false ) {
                $schema_data = json_decode( $schema_json, true );
            }
        }

        // Preload the settings endpoint response.
        $preload_paths = [
            '/wp/v2/settings',
        ];

        $preload_data = array_reduce(
            $preload_paths,
            'rest_preload_api_request',
            []
        );

        // Add inline script to configure apiFetch preloading middleware
        wp_add_inline_script(
            'wp-api-fetch',
            sprintf( 'wp.apiFetch.use( wp.apiFetch.createPreloadingMiddleware( %s ) );', wp_json_encode( $preload_data ) ),
            'after'
        );

        wp_localize_script(
            'lolly-admin-scripts',
            'lolly',
            [
                'schema' => $schema_data,
                // @todo Double check if this is already performed.
                'nonce'  => wp_create_nonce( 'wp_rest' ),
            ]
        );
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <div id="lolly-settings"></div>
        </div>
        <?php
    }
}
