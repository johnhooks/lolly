<?php

declare(strict_types=1);

namespace Lolly\Processors;

use Lolly\Monolog\LogRecord;
use Lolly\Monolog\Processor\ProcessorInterface;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * CentralVerbProcessor class.
 *
 * Notes: This processor is a placeholder for the future implementation of
 * integration with the Solid Central verb API.
 *
 * @package Lolly
 */
class CentralVerbProcessor implements ProcessorInterface {
    public function __invoke( LogRecord $record ): LogRecord {
        return $record;
    }
}
