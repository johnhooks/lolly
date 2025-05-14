<?php

declare(strict_types=1);

namespace Dozuki\Listeners;

use Dozuki\lucatume\DI52\ServiceProvider;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Provider extends ServiceProvider {
    /**
     * @var class-string[]
     */
    public array $provides = [
        LogOnCentralVerbRequest::class,
        LogOnHttpClientRequest::class,
        LogOnRestApiRequest::class,
    ];

    public function register() {
        add_action( 'solid_central_verb_request', $this->container->callback( LogOnCentralVerbRequest::class, 'handle_request' ) );
        add_action( 'solid_central_verb_response', $this->container->callback( LogOnCentralVerbRequest::class, 'handle_response' ) );
        // The priority should cause this shutdown action to run after the `shutdown` of `ithemes-sync`.
        add_action( 'shutdown', $this->container->callback( LogOnCentralVerbRequest::class, 'shutdown' ), 11 );

        add_action( 'http_api_debug', $this->container->callback( LogOnHttpClientRequest::class, 'handle' ), 999, 5 );
        add_filter( 'rest_post_dispatch', $this->container->callback( LogOnRestApiRequest::class, 'handle' ), 999, 3 );

        $this->container->singleton( LogOnCentralVerbRequest::class, LogOnCentralVerbRequest::class );
        $this->container->bind( LogOnHttpClientRequest::class, LogOnHttpClientRequest::class );
        $this->container->bind( LogOnRestApiRequest::class, LogOnRestApiRequest::class );
    }
}
