<?php declare(strict_types=1);

namespace Dozuki\Processors;

use Dozuki\Monolog\LogRecord;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CentralVerbProcessor {

    public function __invoke( LogRecord $record ) {
    }
}
