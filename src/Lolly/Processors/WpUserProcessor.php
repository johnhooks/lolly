<?php

declare(strict_types=1);

namespace Lolly\Processors;

use Lolly\Monolog\LogRecord;
use Lolly\Monolog\Processor\ProcessorInterface;
use WP_User;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * WpUserProcessor class.
 *
 * Transforms a `WP_User` into a "user" field for ECS/ELK.
 *
 * @link https://www.elastic.co/docs/reference/ecs/ecs-user
 *
 * @package Lolly
 */
class WpUserProcessor implements ProcessorInterface {
    public function __invoke( LogRecord $record ): LogRecord {
        $context = $record->context;
        $extra   = $record->extra;

        foreach ( $context as $key => $value ) {
            if ( $value instanceof WP_User && $value->exists() ) {
                $user_fields = [
                    'id' => $value->ID,
                ];

                if ( $key === 'user' && ! isset( $extra['user'] ) ) {
                    $context['user'] = $user_fields;
                    $extra['user']   = $user_fields;
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
