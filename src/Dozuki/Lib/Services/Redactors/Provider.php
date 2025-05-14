<?php

namespace Dozuki\Lib\Services\Redactors;

use Dozuki\Lib\Contracts\Redactors\Config;
use Dozuki\Lib\Contracts\Redactors\HttpMessage;
use Dozuki\Lib\Services\Redactors\HttpMessage\DefaultRedactor;
use Dozuki\lucatume\DI52\Container;
use Dozuki\lucatume\DI52\ServiceProvider;

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
        HttpMessage::class,
    ];

    public function register() {
        $this->container->bind( HttpMessage::class, fn() => new DefaultRedactor( $this->config ) );
    }
}
