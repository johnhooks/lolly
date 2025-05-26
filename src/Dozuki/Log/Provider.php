<?php

declare(strict_types=1);

namespace Dozuki\Log;

use Dozuki\Lib\Processors\EcsErrorProcessor;
use Dozuki\Lib\Processors\EcsHttpMessageProcessor;
use Dozuki\Lib\Processors\EcsHttpRedactionProcessor;
use Dozuki\Lib\Processors\PsrLogMessageProcessor;
use Dozuki\Lib\Processors\EcsTracingProcessor;
use Dozuki\Lib\Processors\EcsUrlProcessor;
use Dozuki\Processors\WpErrorProcessor;
use Dozuki\Processors\WpHttpClientProcessor;
use Dozuki\Processors\WpRestApiProcessor;
use Dozuki\Processors\WpUserProcessor;
use Dozuki\lucatume\DI52\Container;
use Dozuki\lucatume\DI52\ServiceProvider;
use Dozuki\Psr\Log\LoggerInterface;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Provider extends ServiceProvider {
    /**
     * @var class-string[]
     */
    public array $provides = [
        PsrLogMessageProcessor::class,
        WpUserProcessor::class,
        EcsTracingProcessor::class,
        WpErrorProcessor::class,
        WpHttpClientProcessor::class,
        WpRestApiProcessor::class,
        EcsErrorProcessor::class,
        EcsHttpRedactionProcessor::class,
        EcsUrlProcessor::class,
        EcsHttpMessageProcessor::class,
        LoggerFactory::class,
        LoggerInterface::class,
    ];

    public function register() {
        $this->container->bind( PsrLogMessageProcessor::class, PsrLogMessageProcessor::class );
        $this->container->bind( WpUserProcessor::class, WpUserProcessor::class );
        $this->container->bind( EcsTracingProcessor::class, EcsTracingProcessor::class );
        $this->container->bind( WpErrorProcessor::class, WpErrorProcessor::class );
        $this->container->bind( WpHttpClientProcessor::class, WpHttpClientProcessor::class );
        $this->container->bind( WpRestApiProcessor::class, WpRestApiProcessor::class );
        $this->container->bind( EcsErrorProcessor::class, EcsErrorProcessor::class );
        $this->container->bind( EcsHttpRedactionProcessor::class, EcsHttpRedactionProcessor::class );
        $this->container->bind( EcsUrlProcessor::class, EcsUrlProcessor::class );
        $this->container->bind( EcsHttpMessageProcessor::class, EcsHttpMessageProcessor::class );
        $this->container->bind( LoggerFactory::class, LoggerFactory::class );

        $this->container->singleton(
            LoggerInterface::class,
            fn( Container $container ) => $container->make( LoggerFactory::class )->make()
        );
    }
}
