<?php

declare(strict_types=1);

namespace Dozuki\Log;

use Dozuki\Config\Config;
use Dozuki\Lib\Formatters\EcsLogStashFormatter;
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
use Dozuki\Monolog\Formatter\JsonFormatter;
use Dozuki\Monolog\Handler\StreamHandler;
use Dozuki\Monolog\Level;
use Dozuki\Monolog\Logger;
use Dozuki\Monolog\Processor\ProcessorInterface;
use Dozuki\Psr\Log\LoggerInterface;

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
        LoggerInterface::class,
    ];

    /**
     * List of log processors in order of operation.
     *
     * @var class-string[]
     */
    private array $processors = [
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
    ];

    public function register() {
        $log_file_path = trailingslashit( $this->config->get_log_dir_path() ) . 'dozuki.log';

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

        $this->container->singleton(
            LoggerInterface::class,
            function () use ( $log_file_path ) {
                $handler = new StreamHandler( $log_file_path, Level::Debug );

                // @todo This should be configurable.
                $handler->setFormatter( new EcsLogStashFormatter( 'WordPress' ) );

                return new Logger(
                    'wp',
                    [
                        $handler,
                    ],
                    $this->build_processors(),
                );
            }
        );
    }

    /**
     * @return ProcessorInterface[]
     */
    private function build_processors(): array {
        $processors = [];

        foreach ( $this->processors as $processor ) {
            $processors[] = $this->container->make( $processor );
        }

        return $processors;
    }
}
