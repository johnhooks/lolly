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
        HttpListener::class,
        UserListener::class,
        AuthListener::class,
        FatalErrorListener::class,
    ];

    public function register() {
        $this->container->bind( HttpListener::class, HttpListener::class );
        $this->container->bind( UserListener::class, UserListener::class );
        $this->container->bind( AuthListener::class, AuthListener::class );
        $this->container->bind( FatalErrorListener::class, FatalErrorListener::class );

        $this->register_http_listeners();
        $this->register_user_listeners();
        $this->register_auth_listeners();
        $this->register_fatal_error_listener();
    }

    private function register_http_listeners(): void {
        if ( $this->config->is_wp_http_client_logging_enabled() ) {
            add_action(
                'http_api_debug',
                $this->container->callback( HttpListener::class, 'on_client_request' ),
                999,
                5
            );
        }

        if ( $this->config->is_wp_rest_logging_enabled() ) {
            add_filter(
                'rest_post_dispatch',
                $this->container->callback( HttpListener::class, 'on_rest_request' ),
                999,
                3
            );
        }
    }

    private function register_user_listeners(): void {
        if ( ! $this->config->is_wp_user_event_logging_enabled() ) {
            return;
        }

        add_action(
            'user_register',
            $this->container->callback( UserListener::class, 'on_created' ),
            10,
            2
        );

        add_action(
            'delete_user',
            $this->container->callback( UserListener::class, 'on_deleted' ),
            10,
            3
        );

        add_action(
            'add_user_role',
            $this->container->callback( UserListener::class, 'on_role_added' ),
            10,
            2
        );

        add_action(
            'set_user_role',
            $this->container->callback( UserListener::class, 'on_role_changed' ),
            10,
            3
        );

        add_action(
            'remove_user_role',
            $this->container->callback( UserListener::class, 'on_role_removed' ),
            10,
            2
        );
    }

    private function register_auth_listeners(): void {
        if ( $this->config->is_auth_login_logging_enabled() ) {
            add_action(
                'wp_login',
                $this->container->callback( AuthListener::class, 'on_login' ),
                10,
                2
            );
        }

        if ( $this->config->is_auth_logout_logging_enabled() ) {
            add_action(
                'wp_logout',
                $this->container->callback( AuthListener::class, 'on_logout' ),
                10,
                1
            );
        }

        if ( $this->config->is_auth_login_failed_logging_enabled() ) {
            add_action(
                'wp_login_failed',
                $this->container->callback( AuthListener::class, 'on_login_failed' ),
                10,
                2
            );
        }

        if ( $this->config->is_auth_password_changed_logging_enabled() ) {
            add_action(
                'after_password_reset',
                $this->container->callback( AuthListener::class, 'on_password_reset' ),
                10,
                1
            );

            add_action(
                'profile_update',
                $this->container->callback( AuthListener::class, 'on_profile_update' ),
                10,
                3
            );
        }

        if ( $this->config->is_auth_app_password_created_logging_enabled() ) {
            add_action(
                'wp_create_application_password',
                $this->container->callback( AuthListener::class, 'on_app_password_created' ),
                10,
                3
            );
        }

        if ( $this->config->is_auth_app_password_deleted_logging_enabled() ) {
            add_action(
                'wp_delete_application_password',
                $this->container->callback( AuthListener::class, 'on_app_password_deleted' ),
                10,
                2
            );
        }
    }

    private function register_fatal_error_listener(): void {
        if ( ! $this->config->is_logging_enabled() ) {
            return;
        }

        register_shutdown_function(
            $this->container->callback( FatalErrorListener::class, 'on_shutdown' )
        );
    }
}
