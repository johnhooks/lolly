<?php

declare(strict_types=1);

namespace Lolly\Lib\Processors;

use Lolly\Monolog\LogRecord;
use Lolly\Monolog\Processor\ProcessorInterface;
use Lolly\Monolog\ResettableInterface;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * EcsTracingProcessor class.
 *
 * Adds the "transaction" field for ECS/ELK.
 *
 * @link https://www.elastic.co/guide/en/ecs/current/ecs-tracing
 *
 * @package Lolly
 */
class EcsTracingProcessor implements ProcessorInterface, ResettableInterface {

    private string $uid;

    public function __construct( int $length = 7 ) {
        if ( $length > 32 || $length < 1 ) {
            throw new \InvalidArgumentException( 'The uid length must be an integer between 1 and 32' );
        }

        $this->uid = $this->generate_uid( $length );
    }

    public function __invoke( LogRecord $record ): LogRecord {
        $record->extra['transaction'] = [
            'id' => $this->uid,
        ];

        return $record;
    }

    public function get_uid(): string {
        return $this->uid;
    }

    public function reset(): void {
        $this->uid = $this->generate_uid( strlen( $this->uid ) );
    }

    private function generate_uid( int $length ): string {
        return substr( hash( 'md5', uniqid( '', true ) ), 0, $length );
    }
}
