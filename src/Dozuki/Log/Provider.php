<?php

declare(strict_types=1);

namespace Dozuki\Log;

use Dozuki\Config\Config;
use Dozuki\Lib\Processors\EcsErrorProcessor;
use Dozuki\Lib\Processors\EcsHttpMessageProcessor;
use Dozuki\Lib\Processors\EcsHttpRedactionProcessor;
use Dozuki\Lib\Processors\PsrLogMessageProcessor;
use Dozuki\Lib\Processors\EcsTracingProcessor;
use Dozuki\Processors\UrlProcessor;
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

    public function register() {
        $log_file_path = trailingslashit( $this->config->get_log_dir_path() ) . 'dozuki.log';

        $this->container->singleton(
            LoggerInterface::class,
            function () use ( $log_file_path ) {
                $handler = new StreamHandler( $log_file_path, Level::Debug );
                $handler->setFormatter( new JsonFormatter() );

                return new Logger(
                    'wp',
                    [
                        $handler,
                    ],
                    [
                        new PsrLogMessageProcessor(),
                        new WpUserProcessor(),
                        new EcsTracingProcessor(),
                        new UrlProcessor(),
                        new WpErrorProcessor(),
                        new WpHttpClientProcessor(),
                        new WpRestApiProcessor(),
                        new EcsErrorProcessor(),
                        new EcsHttpMessageProcessor(),
                        new EcsHttpRedactionProcessor(),
                    ]
                );
            }
        );
    }
}
