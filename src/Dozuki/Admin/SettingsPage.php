<?php

namespace Dozuki\Admin;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handles the admin settings page for Dozuki.
 */
class SettingsPage {
    public const MENU_SLUG = 'dozuki-settings';

    /**
     * Add admin menu page
     */
    public function add_admin_menu() {
        add_options_page(
            __( 'Dozuki Log Settings', 'dozuki' ),
            __( 'Dozuki Log', 'dozuki' ),
            'manage_options',
            self::MENU_SLUG,
            [ $this, 'render_settings_page' ]
        );
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_assets( $hook ) {
        if ( 'settings_page_dozuki-settings' !== $hook ) {
            return;
        }

        $asset_path = plugin_dir_path( DOZUKI_PLUGIN_FILE ) . 'build/admin.asset.php';

        if ( file_exists( $asset_path ) ) {
            $asset = require $asset_path;
        }

        if ( ! isset( $asset ) ) {
            dozuki()->error(
                '[Dozuki] Missing settings page asset file.',
                [
                    'asset_path' => $asset_path,
                ]
            );

            return;
        }

        wp_enqueue_style(
            'dozuki-admin-styles',
            plugin_dir_url( DOZUKI_PLUGIN_FILE ) . 'build/style-admin.css',
            [ 'wp-components' ],
            $asset['version']
        );

        wp_enqueue_script(
            'dozuki-admin-scripts',
            plugin_dir_url( DOZUKI_PLUGIN_FILE ) . 'build/admin.js',
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
            <div id="dozuki-settings" class="dozuki-settings"></div>
        </div>
        <?php
    }
}
