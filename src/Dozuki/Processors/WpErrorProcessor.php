<?php

declare(strict_types=1);

namespace Dozuki\Processors;

use Dozuki\Monolog\LogRecord;
use Dozuki\Monolog\Processor\ProcessorInterface;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WpErrorProcessor implements ProcessorInterface {
    public function __invoke( LogRecord $record ): LogRecord {
        $context = $record->context;
        $extra   = $record->extra;

        foreach ( $context as $key => $value ) {
            if ( ! is_wp_error( $value ) ) {
                continue;
            }

            /**
             *@todo Capture all the error data.
             */
            if ( ! isset( $extra['error'] ) ) {
                $extra['error'] = [
                    'code'    => $value->get_error_code(),
                    'message' => $value->get_error_message(),
                    'type'    => 'WP_Error',
                ];

                // @todo The error fields doesn't provide the structure to
                // capture the entire WP_Error object. For now leave the value
                // in the context.
                // unset( $context[ $key ] );
            }
        }

        return $record->with( context: $context, extra: $extra );
    }
}
