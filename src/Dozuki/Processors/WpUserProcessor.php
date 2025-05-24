<?php

declare(strict_types=1);

namespace Dozuki\Processors;

use Dozuki\Monolog\LogRecord;
use Dozuki\Monolog\Processor\ProcessorInterface;
use WP_User;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WpUserProcessor implements ProcessorInterface {
    public function __invoke( LogRecord $record ): LogRecord {
        $context = $record->context;
        $extra   = $record->extra;

        foreach ( $context as $key => $value ) {
            if ( $value instanceof Wp_User && $value->exists() ) {
                $user_fields = [
                    'id' => $value->ID,
                ];

                if ( $key === 'user' && ! isset( $extra['user'] ) ) {
                    $extra['user'] = $user_fields;

                    unset( $context[ $key ] );
                } else {
                    $context[ $key ] = $user_fields;
                }
            }
        }

        if ( ! isset( $extra['user'] ) ) {
            /** @var ?WP_User $current_user */
            $current_user = wp_get_current_user();

            if ( $current_user !== null && $current_user->exists() ) {
                $user_fields = [
                    'id' => $current_user->ID,
                ];

                $extra['user'] = $user_fields;
            }
        }

        return $record->with( context: $context, extra: $extra );
    }
}
