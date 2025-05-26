<?php

declare(strict_types=1);

namespace Dozuki\Log;

use Dozuki\Config\Config;
use Dozuki\Lib\Formatters\EcsLogStashFormatter;
use Dozuki\Lib\Processors\EcsErrorProcessor;
use Dozuki\Lib\Processors\EcsHttpMessageProcessor;
use Dozuki\Lib\Processors\EcsHttpRedactionProcessor;
use Dozuki\Lib\Processors\EcsTracingProcessor;
use Dozuki\Lib\Processors\EcsUrlProcessor;
use Dozuki\Lib\Processors\PsrLogMessageProcessor;
use Dozuki\lucatume\DI52\Container;
use Dozuki\Monolog\Handler\StreamHandler;
use Dozuki\Monolog\Level;
use Dozuki\Monolog\Logger;
use Dozuki\Monolog\Processor\ProcessorInterface;
use Dozuki\Processors\WpErrorProcessor;
use Dozuki\Processors\WpHttpClientProcessor;
use Dozuki\Processors\WpRestApiProcessor;
use Dozuki\Processors\WpUserProcessor;
use Dozuki\Psr\Log\LoggerInterface;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * LoggerFactory class.
 *
 * @package Dozuki
 */
class LoggerFactory {
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

    public function __construct(
        private readonly Container $container,
        private readonly Config $config
    ) {}

    public function make(): LoggerInterface {
        $log_file_path = trailingslashit( $this->config->get_log_dir_path() ) . 'dozuki.log';

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

    /**
     * @return ProcessorInterface[]
     */
    private function build_processors(): array {
        $processors = [];

        $http_redactions_enabled = $this->config->is_http_redactions_enabled();

        foreach ( $this->processors as $processor ) {
            if ( ! $http_redactions_enabled && $processor === EcsHttpRedactionProcessor::class ) {
                continue;
            }

            $processors[] = $this->container->make( $processor );
        }

        return $processors;
    }
}
