<?php

declare(strict_types=1);

namespace Tests\Wpunit\Listeners;

use Lolly\Listeners\LogOnLoginFailed;
use lucatume\WPBrowser\TestCase\WPTestCase;
use Tests\Support\WpunitTester;
use WP_Error;

/**
 * @property WpunitTester $tester
 */
class LogOnLoginFailedTest extends WPTestCase {
    public function _before(): void {
        parent::_before();

        $this->tester->updateSettings(
            [
                'enabled'                 => true,
                'wp_auth_logging_enabled' => true,
                'wp_auth_logging_config'  => [
                    'login'                => true,
                    'logout'               => true,
                    'login_failed'         => true,
                    'password_changed'     => true,
                    'app_password_created' => true,
                    'app_password_deleted' => true,
                ],
            ]
        );

        add_action(
            'wp_login_failed',
            lolly()->callback( LogOnLoginFailed::class, 'handle' ),
            10,
            2
        );
    }

    public function _after(): void {
        remove_all_actions( 'wp_login_failed' );

        parent::_after();
    }

    public function testLogsLoginFailed(): void {
        $error = new WP_Error( 'incorrect_password', 'The password you entered is incorrect.' );

        $this->tester->fakeLogger();

        do_action( 'wp_login_failed', 'testuser', $error );

        $this->tester->seeLogMessage( 'Login failed.', 'warning' );
    }

    public function testCapturesUsernameAndError(): void {
        $error = new WP_Error( 'incorrect_password', 'The password you entered is incorrect.' );

        $this->tester->fakeLogger();

        do_action( 'wp_login_failed', 'attackeduser', $error );

        $records = $this->tester->grabLogRecords();
        $this->assertCount( 1, $records );

        $context = $records[0]->context;
        $this->assertArrayHasKey( 'username', $context );
        $this->assertArrayHasKey( 'wp_error', $context );
        $this->assertEquals( 'attackeduser', $context['username'] );
    }
}
