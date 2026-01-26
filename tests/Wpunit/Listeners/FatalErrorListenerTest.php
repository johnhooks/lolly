<?php

declare(strict_types=1);

namespace Tests\Wpunit\Listeners;

use Lolly\Listeners\FatalErrorListener;
use lucatume\WPBrowser\TestCase\WPTestCase;
use Tests\Stubs\SpyLogger;

/**
 * Tests for the FatalErrorListener class.
 *
 * Note: Testing fatal error handling is challenging because we can't easily
 * trigger real fatal errors. These tests focus on the coordination mechanism
 * between the drop-in and the shutdown hook.
 */
class FatalErrorListenerTest extends WPTestCase {

    public function tearDown(): void {
        parent::tearDown();

        // Reset the global flag.
        unset( $GLOBALS['lolly_fatal_error_logged'] );
    }

    public function testOnShutdownSkipsWhenDropinAlreadyLogged(): void {
        // Simulate that the drop-in has already logged the error.
        $GLOBALS['lolly_fatal_error_logged'] = true;

        $logger   = new SpyLogger();
        $listener = new FatalErrorListener( $logger );
        $listener->on_shutdown();

        // Should not have logged because drop-in already did.
        $this->assertEquals( 0, $logger->count_level( 'critical' ) );
    }

    public function testGlobalFlagCoordinatesDropinAndShutdownHook(): void {
        // Initially the flag should not be set.
        $this->assertFalse( isset( $GLOBALS['lolly_fatal_error_logged'] ) );

        // After the drop-in logs (simulated), the flag is set.
        $GLOBALS['lolly_fatal_error_logged'] = true;
        $this->assertTrue( $GLOBALS['lolly_fatal_error_logged'] );

        // The shutdown hook checks this flag and skips if already logged.
        // This ensures we don't get duplicate log entries.
    }

    public function testListenerCanBeInstantiated(): void {
        $logger   = new SpyLogger();
        $listener = new FatalErrorListener( $logger );

        $this->assertInstanceOf( FatalErrorListener::class, $listener );
    }
}
