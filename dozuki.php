<?php

declare(strict_types=1);

/*
 * Plugin Name: Duzuki Logger
 * Plugin URI: http://okaywp.com/duzuki/
 * Description: A logging plugin.
 * Author: John Hooks
 * Version: 1.0.0
 * Author URI: http://johnhooks.io/
 * Requires at least: 6.5
 * Requires PHP: 8.1
*/

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * @package Duzuki
 */

use Dozuki\Admin;
use Dozuki\Config;
use Dozuki\Listeners;
use Dozuki\Log;
use Dozuki\Plugin\Activator;
use Dozuki\Plugin\Uninstaller;
use Dozuki\lucatume\DI52\Container;
use Dozuki\Psr\Log\LogLevel;
use Dozuki\Psr\Log\LoggerInterface;

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/vendor/vendor-prefixed/autoload.php';

/**
 * @method void bind(string $abstract, mixed $concrete) Binds an interface, a class or a string slug to a concrete.
 * @method mixed has(string $abstract) Returns true if the container can return an entry for the given identifier.
 *         Returns false otherwise.
 * @method mixed get(string $abstract) Finds an entry of the container by its identifier and returns it.
 * @method mixed make(string $abstract) Returns an instance of the class or object bound to an interface, class or
 *         string slug if any, else it will try to automagically resolve the object to a usable instance.
 * @method void setContainer(Container $container) Sets the container instance the plugin should use as a Service
 *         Locator.
 */
final class Dozuki implements LoggerInterface {
    private Container $container;

    /**
     * @var class-string[] Array of Service Providers to load.
     */
    private array $service_providers = [
        Admin\Provider::class,
        Config\Provider::class,
        Listeners\Provider::class,
        Log\Provider::class,
    ];

    /**
     * Logged messages that have not yet been written to the log file.
     *
     * NOTE: This buffer is used to buffer messages until the plugin is fully initialized.
     *
     * @var list<array{0: string, 1: string, 2: array<string, mixed>}> Log messages to be written.
     */
    private array $buffered_log_messages = [];

    /**
     * @var bool Make sure the providers are loaded only once.
     */
    private bool $have_providers_loaded = false;

    /**
     * Dozuki constructor.
     *
     * Sets up the Container to be used for managing all other instances and data
     */
    public function __construct() {
        $this->container = new Container();
    }

    /**
     * Init Dozuki when WordPress Initializes.
     */
    public function init(): void {
        $this->load_service_providers();

        if ( count( $this->buffered_log_messages ) !== 0 ) {
            foreach ( $this->buffered_log_messages as $message ) {
                $this->log( $message[0], $message[1], $message[2] );
            }
        }
    }

    /**
     * Load all the service providers to bootstrap the various parts of the application.
     */
    private function load_service_providers(): void {
        if ( $this->have_providers_loaded ) {
            return;
        }

        foreach ( $this->service_providers as $provider ) {
            $this->container->register( $provider );
        }

        $this->container->boot();

        $this->have_providers_loaded = true;
    }

    /**
     * Bootstraps the Dozuki Plugin.
     */
    public function boot(): void {
        $this->setup_constants();

        add_action( 'plugins_loaded', [ $this, 'init' ], 0 );
    }

    /**
     * Setup plugin constants
     *
     * @access private
     */
    private function setup_constants(): void {
        // Plugin version.
        if ( ! defined( 'DOZUKI_VERSION' ) ) {
            define( 'DOZUKI_VERSION', '0.1.0' );
        }

        // Plugin Root File.
        if ( ! defined( 'DOZUKI_PLUGIN_FILE' ) ) {
            define( 'DOZUKI_PLUGIN_FILE', __FILE__ );
        }

        // Plugin Folder Path.
        if ( ! defined( 'DOZUKI_PLUGIN_DIR' ) ) {
            define( 'DOZUKI_PLUGIN_DIR', plugin_dir_path( DOZUKI_PLUGIN_FILE ) );
        }

        // Plugin Folder URL.
        if ( ! defined( 'DOZUKI_PLUGIN_URL' ) ) {
            define( 'DOZUKI_PLUGIN_URL', plugin_dir_url( DOZUKI_PLUGIN_FILE ) );
        }

        // Plugin Basename aka: "dozuki/dozuki.php".
        if ( ! defined( 'DOZUKI_PLUGIN_BASENAME' ) ) {
            define( 'DOZUKI_PLUGIN_BASENAME', plugin_basename( DOZUKI_PLUGIN_FILE ) );
        }

        if ( ! defined( 'DOZUKI_LOG_HTTP_ENABLED' ) ) {
            define( 'DOZUKI_LOG_HTTP_ENABLED', false );
        }

        if ( ! defined( 'DOZUKI_LOG_DIR' ) ) {
            define( 'DOZUKI_LOG_DIR', WP_CONTENT_DIR );
        }
    }

    /**
     * Get the service container.
     *
     * @return Container
     */
    public function get_container(): Container {
        return $this->container;
    }

    /**
     * Set the service container.
     *
     * @param Container $container
     *
     * @return void
     */
    public function set_container( Container $container ): void {
        $this->container = $container;
    }

    /**
     * Magic methods are passed to the service container.
     *
     * @param string       $name
     * @param array<mixed> $arguments
     *
     * @return mixed
     */
    public function __call( $name, $arguments ) {
        return call_user_func_array( [ $this->container, $name ], $arguments );
    }

    /**
     * System is unusable.
     *
     * @param string|Stringable $message
     * @param array             $context
     *
     * @return void
     */
    public function emergency( $message, array $context = [] ): void {
        $this->log( LogLevel::EMERGENCY, $message, $context );
    }

    /**
     * Action must be taken immediately.
     *
     * Example: Entire website down, database unavailable, etc. This should
     * trigger the SMS alerts and wake you up.
     *
     * @param string|Stringable $message
     * @param array             $context
     *
     * @return void
     */
    public function alert( $message, array $context = [] ): void {
        $this->log( LogLevel::ALERT, $message, $context );
    }

    /**
     * Critical conditions.
     *
     * Example: Application component unavailable, unexpected exception.
     *
     * @param string|Stringable $message
     * @param array             $context
     *
     * @return void
     */
    public function critical( $message, array $context = [] ): void {
        $this->log( LogLevel::CRITICAL, $message, $context );
    }

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @param string|Stringable $message
     * @param array             $context
     *
     * @return void
     */
    public function error( $message, array $context = [] ): void {
        $this->log( LogLevel::ERROR, $message, $context );
    }

    /**
     * Exceptional occurrences that are not errors.
     *
     * Example: Use of deprecated APIs, poor use of an API, undesirable things
     * that are not necessarily wrong.
     *
     * @param string|Stringable $message
     * @param array             $context
     *
     * @return void
     */
    public function warning( $message, array $context = [] ): void {
        $this->log( LogLevel::WARNING, $message, $context );
    }

    /**
     * Normal but significant events.
     *
     * @param string $message
     * @paramarray $context
     * @return void
     */
    public function notice( $message, array $context = [] ): void {
        $this->log( LogLevel::NOTICE, $message, $context );
    }

    /**
     * Interesting events.
     *
     * Example: User logs in, SQL logs.
     *
     * @param string|Stringable $message
     * @paramarray $context
     * @return void
     */
    public function info( string|Stringable $message, array $context = [] ): void {
        $this->log( LogLevel::INFO, $message, $context );
    }

    /**
     * Detailed debug information.
     *
     * @param string $message
     * @paramarray $context
     *
     * @return void
     */
    public function debug( $message, array $context = [] ): void {
        $this->log( LogLevel::DEBUG, $message, $context );
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed  $level
     * @param string $message
     * @paramarray $context
     *
     * @return void
     */
    public function log( $level, $message, array $context = [] ): void {
        if ( ! $this->have_providers_loaded ) {
            $this->buffered_log_messages[] = [ $level, $message, $context ];
            return;
        }

        $this->container->get( LoggerInterface::class )->log( $level, $message, $context );
    }
}

/**
 * Start Dozuki
 *
 * The main function responsible for returning the one true Dozuki instance to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * @template Abstract
 *
 * @param class-string<Abstract>|null $abstract Selector for data to retrieve from the service container.
 *
 * @return ( $abstract is null ? Dozuki : Abstract )
 */
function dozuki( string $abstract = null ): mixed {
    static $instance = null;

    if ( $instance === null ) {
        $instance = new Dozuki();
    }

    if ( $abstract !== null ) {
        return $instance->make( $abstract );
    }

    return $instance;
}

dozuki()->boot();

register_activation_hook( __FILE__, Activator::callback() );
register_uninstall_hook( __FILE__, [ Uninstaller::class, 'uninstall' ] );
