<?php
/**
 * All functionality related to activating the plugin.
 *
 * @package Dozuki
 */

declare(strict_types=1);

namespace Dozuki\Plugin;

use Dozuki\Config\Config;
use Dozuki\lucatume\DI52\Container;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handles actions that should run when the plugin is activated.
 *
 * @package Dozuki
 */
final class Activator {
    /**
     * @var Container
     */
    private Container $container;

    /**
     * @param Container $container The container.
     */
    private function __construct( Container $container ) {
        $this->container = $container;
    }

    /**
     * Lazy-instantiated callable for register_activation_hook.
     *
     * @return callable
     */
    public static function callback(): callable {
        return static function (): void {
            dozuki()->init();

            $instance = new self( dozuki()->get_container() );

            $instance->activate();
        };
    }

    /**
     * Activation hook.
     *
     * @return void
     */
    private function activate(): void {
        $this->write_settings();
    }

    /**
     * Saves a set of default values in the config.
     *
     * @return void
     */
    private function write_settings(): void {
        $existing = get_option( Config::OPTION_SLUG );

        if ( is_array( $existing ) ) {
            return;
        }

        $this->container->get( Config::class )->save();
    }
}
