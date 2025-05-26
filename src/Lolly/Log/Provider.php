<?php

declare(strict_types=1);

namespace Lolly\Log;

use Lolly\Lib\Processors\EcsErrorProcessor;
use Lolly\Lib\Processors\EcsHttpMessageProcessor;
use Lolly\Lib\Processors\EcsHttpRedactionProcessor;
use Lolly\Lib\Processors\PsrLogMessageProcessor;
use Lolly\Lib\Processors\EcsTracingProcessor;
use Lolly\Lib\Processors\EcsUrlProcessor;
use Lolly\Processors\WpErrorProcessor;
use Lolly\Processors\WpHttpClientProcessor;
use Lolly\Processors\WpRestApiProcessor;
use Lolly\Processors\WpUserProcessor;
use Lolly\lucatume\DI52\Container;
use Lolly\lucatume\DI52\ServiceProvider;
use Lolly\Psr\Log\LoggerInterface;

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
