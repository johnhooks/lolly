<?php

declare(strict_types=1);

namespace Tests\Support\Helper;

use Codeception\Module;
use Codeception\TestInterface;
use Lolly\Config\Config;
use Lolly\Log\LoggerFactory;
use Lolly\Monolog\Handler\TestHandler;
use Lolly\Monolog\LogRecord;
use Lolly\Psr\Log\LoggerInterface;

/**
 * Lolly-specific test helper.
 *
 * Provides methods for configuring plugin settings and capturing logs.
 */
class Lolly extends Module {
    private ?TestHandler $testHandler = null;
    private bool $loggerFaked         = false;

    public function _after( TestInterface $test ): void {
        delete_option( Config::OPTION_SLUG );

        if ( $this->loggerFaked ) {
            $this->restoreLogger();
        }

        $this->testHandler = null;
    }

    /**
     * Replace the logger with a fake that captures log records.
     *
     * The fake logger uses the same processors as the real logger,
     * but writes to a TestHandler instead of a file.
     */
    public function fakeLogger(): void {
        $this->testHandler = new TestHandler();
        $this->loggerFaked = true;

        /** @var LoggerFactory $factory */
        $factory = lolly( LoggerFactory::class );
        $logger  = $factory->make();

        // Replace the file handler with our test handler.
        $logger->setHandlers( [ $this->testHandler ] );

        // Rebind the singleton to use our fake logger.
        lolly()->singleton( LoggerInterface::class, static fn() => $logger );
    }

    /**
     * Restore the real logger singleton.
     */
    private function restoreLogger(): void {
        lolly()->singleton(
            LoggerInterface::class,
            static fn() => lolly( LoggerFactory::class )->make()
        );

        $this->loggerFaked = false;
    }

    /**
     * Update plugin settings and reload the config.
     *
     * @param array<string, mixed> $settings Settings to merge with defaults.
     */
    public function updateSettings( array $settings ): void {
        $defaults = [
            'version'                => 1,
            'enabled'                => false,
            'wp_http_client_logging' => [ 'enabled' => false ],
            'wp_rest_logging'        => [ 'enabled' => false ],
            'wp_user_event_logging'  => [ 'enabled' => false ],
            'wp_auth_logging'        => [
                'enabled'              => false,
                'login'                => true,
                'logout'               => true,
                'login_failed'         => false,
                'password_changed'     => true,
                'app_password_created' => true,
                'app_password_deleted' => true,
            ],
            'http_redactions'        => [
                'enabled' => false,
                'rules'   => [],
            ],
            'http_whitelist'         => [
                'enabled' => false,
                'rules'   => [],
            ],
        ];

        update_option( Config::OPTION_SLUG, array_replace_recursive( $defaults, $settings ) );

        // Reload the cached config.
        lolly( Config::class )->reload();
    }

    /**
     * Grab all captured log records.
     *
     * @return LogRecord[]
     */
    public function grabLogRecords(): array {
        return $this->testHandler?->getRecords() ?? [];
    }

    /**
     * Grab the number of captured log records.
     */
    public function grabLogCount(): int {
        return count( $this->grabLogRecords() );
    }

    /**
     * Clear all captured log records.
     */
    public function clearLogRecords(): void {
        $this->testHandler?->clear();
    }

    /**
     * Check if a message was logged (partial match).
     *
     * @param string $message Message to search for.
     * @param string $level   Optional log level to filter by.
     */
    protected function hasLogMessage( string $message, string $level = '' ): bool {
        foreach ( $this->grabLogRecords() as $record ) {
            if ( $level !== '' && strtolower( $record->level->name ) !== strtolower( $level ) ) {
                continue;
            }

            if ( str_contains( $record->message, $message ) ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Grab records that match a message (partial match).
     *
     * @param string $message Message to search for.
     *
     * @return LogRecord[]
     */
    public function grabLogRecordsWithMessage( string $message ): array {
        $matching = [];

        foreach ( $this->grabLogRecords() as $record ) {
            if ( str_contains( $record->message, $message ) ) {
                $matching[] = $record;
            }
        }

        return $matching;
    }

    /**
     * Assert that a specific number of log records were captured.
     *
     * @param int $expected Expected count.
     */
    public function seeLogCount( int $expected ): void {
        $actual = $this->grabLogCount();
        $this->assertEquals(
            $expected,
            $actual,
            "Expected {$expected} log records, but found {$actual}."
        );
    }

    /**
     * Assert that a log message was recorded.
     *
     * @param string $message Message to find.
     * @param string $level   Optional log level.
     */
    public function seeLogMessage( string $message, string $level = '' ): void {
        $this->assertTrue(
            $this->hasLogMessage( $message, $level ),
            "Expected to find log message containing '{$message}'" . ( $level ? " at level '{$level}'" : '' ) . '.'
        );
    }

    /**
     * Assert that no log message was recorded.
     *
     * @param string $message Message to check.
     * @param string $level   Optional log level.
     */
    public function dontSeeLogMessage( string $message, string $level = '' ): void {
        $this->assertFalse(
            $this->hasLogMessage( $message, $level ),
            "Did not expect to find log message containing '{$message}'" . ( $level ? " at level '{$level}'" : '' ) . '.'
        );
    }
}
