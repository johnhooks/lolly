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
