<?php

declare(strict_types=1);

namespace Dozuki\Processors;

use Dozuki\Monolog\LogRecord;
use Dozuki\Monolog\Processor\ProcessorInterface;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CentralVerbProcessor implements ProcessorInterface {
    public function __invoke( LogRecord $record ): LogRecord {
        return $record;
    }
}
