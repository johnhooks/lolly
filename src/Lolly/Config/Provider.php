<?php

namespace Lolly\Config;

use Lolly\Lib\Contracts\Redactors;
use Lolly\Lib\Contracts\Whitelist;
use Lolly\lucatume\DI52\ServiceProvider;

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
            ->give( fn(): string => LOLLY_LOG_DIR );

        $this->container->singleton( Config::class, Config::class );

        // Note: These are intended to function as aliases.
        $this->container->bind( Redactors\Config::class, fn(): Redactors\Config => $this->container->get( Config::class ) );
        $this->container->bind( Whitelist\Config::class, fn(): Whitelist\Config => $this->container->get( Config::class ) );

        add_action( 'admin_init', $this->container->callback( Config::class, 'register_settings' ) );
        add_action( 'rest_api_init', $this->container->callback( Config::class, 'register_settings' ) );
    }
}
