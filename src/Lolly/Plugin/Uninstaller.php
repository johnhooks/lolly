<?php
/**
 * All functionality related to uninstalling the plugin.
 *
 * @package Lolly
 */

declare( strict_types=1 );

namespace Lolly\Plugin;

use Lolly\Config\Config;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Uninstaller class.
 *
 * Handles actions that should run when the plugin is uninstalled.
 *
 * WordPress does not allow anonymous callbacks with register_uninstall_hook.
 *
 * @package Lolly
 */
final class Uninstaller {
    /**
     * The Singleton instance.
     *
     * @var self|null $instance
     */
    private static ?self $instance = null;

    private function __construct() {}

    /**
     * Get the singleton instance.
     */
    public static function instance(): self {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Uninstall hook run via register_uninstall_hook().
     */
    public static function uninstall(): void {
        self::instance()->handle_uninstall();
    }

    /**
     * Actual uninstall logic.
     */
    private function handle_uninstall(): void {
        delete_option( Config::OPTION_SLUG );
    }
}
