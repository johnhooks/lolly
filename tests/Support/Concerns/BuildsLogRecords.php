<?php

namespace Tests\Support\Concerns;

use DateTimeImmutable;
use Lolly\Monolog\Level;
use Lolly\Monolog\LogRecord;

trait BuildsLogRecords {
    /**
     * Helper to provide defaults to all `LogRecord` constructor args.
     *
     * @param ?DateTimeImmutable $datetime
     * @param string             $channel
     * @param Level              $level
     * @param string             $message
     * @param array              $context
     * @param array              $extra
     *
     * @return LogRecord
     */
    protected function build_log_record(
        ?DateTimeImmutable $datetime = null,
        string $channel = 'test',
        Level $level = Level::Info,
        string $message = '',
        array $context = [],
        array $extra = []
    ): LogRecord {
        return new LogRecord(
            datetime: $datetime ?? new DateTimeImmutable( 'now' ),
            channel: $channel,
            level: $level,
            message: $message,
            context: $context,
            extra: $extra,
        );
    }
}
