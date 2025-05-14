<?php

namespace Dozuki\Admin;

use Dozuki\lucatume\DI52\ServiceProvider;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Admin provider class
 */
class Provider extends ServiceProvider {
    /**
     * @var class-string[]
     */
    public array $provides = [
        SettingsPage::class,
    ];

    /**
     * Register the service provider.
     */
    public function register() {
        $this->container->singleton( SettingsPage::class, SettingsPage::class );

        add_action( 'admin_menu', $this->container->callback( SettingsPage::class, 'add_admin_menu' ), 100 );
        add_action( 'admin_enqueue_scripts', $this->container->callback( SettingsPage::class, 'enqueue_assets' ) );
    }
}
