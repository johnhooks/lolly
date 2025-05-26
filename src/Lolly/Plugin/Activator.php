<?php
/**
 * All functionality related to activating the plugin.
 *
 * @package Lolly
 */

declare(strict_types=1);

namespace Lolly\Plugin;

use Lolly\Config\Config;
use Lolly\lucatume\DI52\Container;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Activator class.
 *
 * Handles actions that should run when the plugin is activated.
 *
 * @package Lolly
 */
final class Activator {
    private function __construct(
        private readonly Container $container
    ) {}

    /**
     * Lazy-instantiated callable for register_activation_hook.
     */
    public static function callback(): callable {
        return static function (): void {
            lolly()->init();

            $instance = new self( lolly()->get_container() );

            $instance->activate();
        };
    }

    /**
     * Activation hook.
     */
    private function activate(): void {
        $this->write_settings();
    }

    /**
     * Saves a set of default values in the config.
     */
    private function write_settings(): void {
        $existing = get_option( Config::OPTION_SLUG );

        if ( is_array( $existing ) ) {
            return;
        }

        $this->container->get( Config::class )->save();
    }
}
