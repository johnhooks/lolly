<?php

declare(strict_types=1);

namespace Lolly\Log;

use Lolly\Config\Config;
use Lolly\Lib\Formatters\EcsLogStashFormatter;
use Lolly\Lib\Processors\EcsErrorProcessor;
use Lolly\Lib\Processors\EcsHttpMessageProcessor;
use Lolly\Lib\Processors\EcsHttpRedactionProcessor;
use Lolly\Lib\Processors\EcsTracingProcessor;
use Lolly\Lib\Processors\EcsUrlProcessor;
use Lolly\Lib\Processors\PsrLogMessageProcessor;
use Lolly\lucatume\DI52\Container;
use Lolly\Monolog\Handler\StreamHandler;
use Lolly\Monolog\Level;
use Lolly\Monolog\Logger;
use Lolly\Monolog\Processor\ProcessorInterface;
use Lolly\Processors\WpErrorProcessor;
use Lolly\Processors\WpHttpClientProcessor;
use Lolly\Processors\WpRestApiProcessor;
use Lolly\Processors\WpUserProcessor;
use Lolly\Psr\Log\LoggerInterface;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * LoggerFactory class.
 *
 * Initializes the Monolog logger.
 *
 * @package Lolly
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
        $log_file_path = trailingslashit( $this->config->get_log_dir_path() ) . 'lolly.log';

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
