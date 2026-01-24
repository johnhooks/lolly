<?php

declare(strict_types=1);

namespace Lolly\Listeners;

use Lolly\Config\Config;
use Lolly\lucatume\DI52\Container;
use Lolly\lucatume\DI52\ServiceProvider;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Provider extends ServiceProvider {
    private readonly Config $config;

    public function __construct( Container $container, Config $config ) {
        parent::__construct( $container );

        $this->config = $config;
    }

    /**
     * @var class-string[]
     */
    public array $provides = [
        LogOnHttpClientRequest::class,
        LogOnRestApiRequest::class,
        LogOnUserCreated::class,
        LogOnUserDeleted::class,
        LogOnUserRoleAdded::class,
        LogOnUserRoleChanged::class,
        LogOnUserRoleRemoved::class,
        LogOnUserLogin::class,
        LogOnUserLogout::class,
        LogOnLoginFailed::class,
        LogOnPasswordChanged::class,
        LogOnApplicationPasswordCreated::class,
        LogOnApplicationPasswordDeleted::class,
    ];

    public function register() {
        if ( $this->config->is_wp_http_client_logging_enabled() ) {
            add_action( 'http_api_debug', $this->container->callback( LogOnHttpClientRequest::class, 'handle' ), 999, 5 );
        }

        if ( $this->config->is_wp_rest_logging_enabled() ) {
            add_filter( 'rest_post_dispatch', $this->container->callback( LogOnRestApiRequest::class, 'handle' ), 999, 3 );
        }

        if ( $this->config->is_wp_user_event_logging_enabled() ) {
            add_action( 'user_register', $this->container->callback( LogOnUserCreated::class, 'handle' ), 10, 2 );
            add_action( 'delete_user', $this->container->callback( LogOnUserDeleted::class, 'handle' ), 10, 3 );
            add_action( 'add_user_role', $this->container->callback( LogOnUserRoleAdded::class, 'handle' ), 10, 2 );
            add_action( 'set_user_role', $this->container->callback( LogOnUserRoleChanged::class, 'handle' ), 10, 3 );
            add_action( 'remove_user_role', $this->container->callback( LogOnUserRoleRemoved::class, 'handle' ), 10, 2 );
        }

        if ( $this->config->is_auth_login_logging_enabled() ) {
            add_action( 'wp_login', $this->container->callback( LogOnUserLogin::class, 'handle' ), 10, 2 );
        }

        if ( $this->config->is_auth_logout_logging_enabled() ) {
            add_action( 'wp_logout', $this->container->callback( LogOnUserLogout::class, 'handle' ), 10, 1 );
        }

        if ( $this->config->is_auth_login_failed_logging_enabled() ) {
            add_action( 'wp_login_failed', $this->container->callback( LogOnLoginFailed::class, 'handle' ), 10, 2 );
        }

        if ( $this->config->is_auth_password_changed_logging_enabled() ) {
            add_action( 'after_password_reset', $this->container->callback( LogOnPasswordChanged::class, 'handle_password_reset' ), 10, 1 );
            add_action( 'profile_update', $this->container->callback( LogOnPasswordChanged::class, 'handle_profile_update' ), 10, 3 );
        }

        if ( $this->config->is_auth_app_password_created_logging_enabled() ) {
            add_action( 'wp_create_application_password', $this->container->callback( LogOnApplicationPasswordCreated::class, 'handle' ), 10, 3 );
        }

        if ( $this->config->is_auth_app_password_deleted_logging_enabled() ) {
            add_action( 'wp_delete_application_password', $this->container->callback( LogOnApplicationPasswordDeleted::class, 'handle' ), 10, 2 );
        }

        $this->container->bind( LogOnHttpClientRequest::class, LogOnHttpClientRequest::class );
        $this->container->bind( LogOnRestApiRequest::class, LogOnRestApiRequest::class );
        $this->container->bind( LogOnUserCreated::class, LogOnUserCreated::class );
        $this->container->bind( LogOnUserDeleted::class, LogOnUserDeleted::class );
        $this->container->bind( LogOnUserRoleAdded::class, LogOnUserRoleAdded::class );
        $this->container->bind( LogOnUserRoleChanged::class, LogOnUserRoleChanged::class );
        $this->container->bind( LogOnUserRoleRemoved::class, LogOnUserRoleRemoved::class );
        $this->container->bind( LogOnUserLogin::class, LogOnUserLogin::class );
        $this->container->bind( LogOnUserLogout::class, LogOnUserLogout::class );
        $this->container->bind( LogOnLoginFailed::class, LogOnLoginFailed::class );
        $this->container->bind( LogOnPasswordChanged::class, LogOnPasswordChanged::class );
        $this->container->bind( LogOnApplicationPasswordCreated::class, LogOnApplicationPasswordCreated::class );
        $this->container->bind( LogOnApplicationPasswordDeleted::class, LogOnApplicationPasswordDeleted::class );
    }
}
