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
    ];

    public function register() {
        if ( $this->config->is_wp_http_client_logging_enabled() ) {
            add_action( 'http_api_debug', $this->container->callback( LogOnHttpClientRequest::class, 'handle' ), 999, 5 );
        }

        if ( $this->config->is_wp_rest_logging_enabled() ) {
            add_filter( 'rest_post_dispatch', $this->container->callback( LogOnRestApiRequest::class, 'handle' ), 999, 3 );
        }

        $this->container->bind( LogOnHttpClientRequest::class, LogOnHttpClientRequest::class );
        $this->container->bind( LogOnRestApiRequest::class, LogOnRestApiRequest::class );
    }
}
