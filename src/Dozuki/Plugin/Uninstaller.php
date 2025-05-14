<?php
/**
 * All functionality related to uninstalling the plugin.
 *
 * @package Dozuki
 */

declare( strict_types=1 );

namespace Dozuki\Plugin;

use Dozuki\Config\Config;
use Dozuki\lucatume\DI52\Container;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handles actions that should run when the plugin is uninstalled.
 *
 * WordPress does not allow anonymous callbacks with register_uninstall_hook.
 *
 * @package Dozuki
 */
final class Uninstaller {
    /**
     * @var Container
     */
    private Container $container;

    /**
     * The Singleton instance.
     *
     * @var self|null
     */
    private static ?self $instance = null;

    /**
     * @param  Container $container The container.
     */
    private function __construct( Container $container ) {
        $this->container = $container;
    }

    /**
     * Get the singleton instance.
     *
     * @return self
     */
    public static function instance(): self {
        if ( self::$instance === null ) {
            self::$instance = new self( dozuki()->get_container() );
        }

        return self::$instance;
    }

    /**
     * Uninstall hook run via register_uninstall_hook().
     *
     * @return void
     */
    public static function uninstall(): void {
        self::instance()->handle_uninstall();
    }

    /**
     * Actual uninstall logic.
     *
     * @return void
     */
    private function handle_uninstall(): void {
        delete_option( Config::OPTION_SLUG );
    }
}
