<?php

declare(strict_types=1);

namespace Lolly;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use Lolly\Lib\Services\Redactors;
use Lolly\lucatume\DI52\Builders\BuilderInterface;
use Lolly\lucatume\DI52\Builders\ValueBuilder;
use Lolly\lucatume\DI52\ServiceProvider;
use Lolly\lucatume\DI52\Container;
use Lolly\Psr\Log\LogLevel;
use Lolly\Psr\Log\LoggerInterface;
use Stringable;

/**
 * Lolly class.
 *
 * The core API for interaction with the Lolly Log plugin.
 *
 * @method mixed setVar(string $key, mixed $value) Sets a value in the container.
 * @method void offsetSet(mixed $offset, mixed $value) Sets a value in the container using array access.
 * @method mixed singleton(string $id, mixed $implementation = null, array $afterBuildMethods = null) Binds an implementation to an interface, a class or a string slug that should be built only once.
 * @method mixed getVar(string $key, mixed $default = null) Returns a value from the container.
 * @method mixed offsetGet(mixed $offset) Returns a value from the container using array access.
 * @method mixed get(string $id) Finds an entry of the container by its identifier and returns it.
 * @method mixed make(string $id) Returns an instance of the class or object bound to an interface, class or string slug if any, else it will try to automagically resolve the object to a usable instance.
 * @method bool offsetExists(mixed $offset) Returns whether a value exists in the container using array access.
 * @method bool has(string $id) Returns true if the container can return an entry for the given identifier. Returns false otherwise.
 * @method void tag(array $implementationsArray, string $tag) Tags an array of implementations with a tag.
 * @method array tagged(string $tag) Returns an array of bound implementations tagged with a specific tag.
 * @method bool hasTag(string $tag) Returns whether a tag exists in the container.
 * @method bool classIsInstantiable(string $class) Returns whether a class is instantiable or not.
 * @method void checkClassIsInstantiatable(string $class) Checks if a class is instantiable and throws an exception if not.
 * @method mixed register(string $serviceProviderClass, string $alias = null) Registers a service provider in the container.
 * @method Closure getDeferredProviderMakeClosure(ServiceProvider $provider, string $id) Returns a closure that will build an instance of the specified class using the specified provider.
 * @method void bind(string $id, mixed $implementation = null, array $afterBuildMethods = null) Binds an interface, a class or a string slug to a concrete implementation.
 * @method void singletonDecorators(string $id, array $decorators, array $afterBuildMethods = null, bool $afterBuildAll = false) Binds a chain of decorators to an interface, a class or a string slug that should be built only once.
 * @method BuilderInterface getDecoratorBuilder(array $decorators, string $id, array $afterBuildMethods = null, bool $afterBuildAll = false) Returns a decorator builder for the specified decorators.
 * @method void bindDecorators(string $id, array $decorators, array $afterBuildMethods = null, bool $afterBuildAll = false) Binds a chain of decorators to an interface, a class or a string slug.
 * @method void offsetUnset(mixed $offset) Unsets a value in the container using array access.
 * @method Container when(string $class) Returns a condition builder for the specified class.
 * @method Container needs(string $id) Returns a condition builder for the specified id.
 * @method void give(mixed $implementation) Binds an implementation to a condition.
 * @method mixed callback(string $id, string $method) Returns a callback for the specified id and method.
 * @method bool isStaticMethod(mixed $object, string $method) Returns whether a method is static or not.
 * @method mixed instance(string $id, array $buildArgs = [], array $afterBuildMethods = null) Returns an instance of the specified class.
 * @method ValueBuilder protect(mixed $value) Returns a value builder for the specified value.
 * @method ServiceProvider getProvider(string $providerId) Returns a service provider instance.
 * @method bool isBound(string $id) Returns whether an id is bound in the container.
 * @method void setExceptionMask(int $maskThrowables) Sets the exception mask for the container.
 * @method Container bindThis() Binds the container itself to the Container class.
 *
 * @package Lolly
 */
final class Lolly implements LoggerInterface {
    private Container $container;

    /**
     * @var class-string[] Array of Service Providers to load.
     */
    private array $service_providers = [
        Admin\Provider::class,
        Config\Provider::class,
        Redactors\Provider::class,
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
     * Lolly constructor.
     *
     * Sets up the Container to be used for managing all other instances and data
     */
    public function __construct() {
        $this->container = new Container();
    }

    /**
     * Init Lolly when WordPress Initializes.
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
     * Bootstraps the Lolly Plugin.
     *
     * @param string $plugin_file The path to the plugin file.
     *
     * @return void
     */
    public function boot( string $plugin_file ): void {
        $this->setup_constants( $plugin_file );

        add_action( 'plugins_loaded', [ $this, 'init' ], 0 );
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
     * Setup plugin constants
     *
     * @param string $plugin_file The path to the plugin file.
     *
     * @return void
     */
    private function setup_constants( string $plugin_file ): void {
        // Plugin version.
        if ( ! defined( 'LOLLY_VERSION' ) ) {
            define( 'LOLLY_VERSION', '0.1.0' );
        }

        // Plugin Root File.
        if ( ! defined( 'LOLLY_PLUGIN_FILE' ) ) {
            define( 'LOLLY_PLUGIN_FILE', $plugin_file );
        }

        // Plugin Folder Path.
        if ( ! defined( 'LOLLY_PLUGIN_DIR' ) ) {
            define( 'LOLLY_PLUGIN_DIR', plugin_dir_path( LOLLY_PLUGIN_FILE ) );
        }

        // Plugin Folder URL.
        if ( ! defined( 'LOLLY_PLUGIN_URL' ) ) {
            define( 'LOLLY_PLUGIN_URL', plugin_dir_url( LOLLY_PLUGIN_FILE ) );
        }

        // Plugin Basename aka: "lolly/lolly.php".
        if ( ! defined( 'LOLLY_PLUGIN_BASENAME' ) ) {
            define( 'LOLLY_PLUGIN_BASENAME', plugin_basename( LOLLY_PLUGIN_FILE ) );
        }

        if ( ! defined( 'LOLLY_LOG_HTTP_ENABLED' ) ) {
            define( 'LOLLY_LOG_HTTP_ENABLED', false );
        }

        if ( ! defined( 'LOLLY_LOG_DIR' ) ) {
            define( 'LOLLY_LOG_DIR', WP_CONTENT_DIR );
        }
    }

    /**
     * Get the service container.
     */
    public function get_container(): Container {
        return $this->container;
    }

    /**
     * Set the service container.
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
     *
     * @since 0.1.0
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
     *
     * @since 0.1.0
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
     *
     * @since 0.1.0
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
     *
     * @since 0.1.0
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
     *
     * @since 0.1.0
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
     *
     * @since 0.1.0
     */
    public function warning( $message, array $context = [] ): void {
        $this->log( LogLevel::WARNING, $message, $context );
    }

    /**
     * Normal but significant events.
     *
     * @param string $message
     * @param array  $context
     *
     * @return void
     *
     * @since 0.1.0
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
     * @param array             $context
     *
     * @return void
     *
     * @since 0.1.0
     */
    public function info( string|Stringable $message, array $context = [] ): void {
        $this->log( LogLevel::INFO, $message, $context );
    }

    /**
     * Detailed debug information.
     *
     * @param string|Stringable $message
     * @param array             $context
     *
     * @return void
     *
     * @since 0.1.0
     */
    public function debug( $message, array $context = [] ): void {
        $this->log( LogLevel::DEBUG, $message, $context );
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed             $level
     * @param string|Stringable $message
     * @param array             $context
     *
     * @return void
     *
     * @since 0.1.0
     */
    public function log( $level, $message, array $context = [] ): void {
        // @todo This seems like it should be handled differently, perhaps a null handler.
        if ( ! $this->container->get( Config\Config::class )->is_logging_enabled() ) {
            return;
        }

        if ( ! $this->have_providers_loaded ) {
            $this->buffered_log_messages[] = [ $level, $message, $context ];
            return;
        }

        $this->container->get( LoggerInterface::class )->log( $level, $message, $context );
    }
}
