<?php

namespace Dozuki\Config;

use Dozuki\lucatume\DI52\ServiceProvider;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Provider extends ServiceProvider {
    /**
     * @var class-string[]
     */
    public array $provides = [
        Config::class,
    ];

    public function register() {
        $this->container->when( Config::class )
            ->needs( '$log_dir_path' )
            ->give( fn(): string => DOZUKI_LOG_DIR );

        $this->container->singleton( Config::class, Config::class );

        add_action( 'admin_init', $this->container->callback( Config::class, 'register_settings' ) );
        add_action( 'rest_api_init', $this->container->callback( Config::class, 'register_settings' ) );
    }
}
