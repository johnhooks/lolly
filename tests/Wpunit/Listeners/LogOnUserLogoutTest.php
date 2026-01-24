<?php

declare(strict_types=1);

namespace Tests\Wpunit\Listeners;

use Lolly\Listeners\LogOnUserLogout;
use lucatume\WPBrowser\TestCase\WPTestCase;
use Tests\Support\WpunitTester;

/**
 * @property WpunitTester $tester
 */
class LogOnUserLogoutTest extends WPTestCase {
    public function _before(): void {
        parent::_before();

        $this->tester->updateSettings(
            [
                'enabled'                 => true,
                'wp_auth_logging_enabled' => true,
                'wp_auth_logging_config'  => [
                    'login'                => true,
                    'logout'               => true,
                    'login_failed'         => false,
                    'password_changed'     => true,
                    'app_password_created' => true,
                    'app_password_deleted' => true,
                ],
            ]
        );

        add_action(
            'wp_logout',
            lolly()->callback( LogOnUserLogout::class, 'handle' ),
            10,
            1
        );
    }

    public function _after(): void {
        remove_all_actions( 'wp_logout' );

        parent::_after();
    }

    public function testLogsUserLogout(): void {
        $user_id = self::factory()->user->create( [ 'role' => 'subscriber' ] );

        $this->tester->fakeLogger();

        do_action( 'wp_logout', $user_id );

        $this->tester->seeLogMessage( 'User logged out.', 'info' );
    }

    public function testCapturesTargetUserId(): void {
        $user_id = self::factory()->user->create( [ 'role' => 'editor' ] );

        $this->tester->fakeLogger();

        do_action( 'wp_logout', $user_id );

        $records = $this->tester->grabLogRecords();
        $this->assertCount( 1, $records );

        $context = $records[0]->context;
        $this->assertArrayHasKey( 'target_user', $context );
        $this->assertIsArray( $context['target_user'] );
        $this->assertEquals( $user_id, $context['target_user']['id'] );
    }

    public function testCapturesActorInExtra(): void {
        $user_id = self::factory()->user->create( [ 'role' => 'subscriber' ] );

        // Simulate the logged in user who is logging out.
        wp_set_current_user( $user_id );

        $this->tester->fakeLogger();

        do_action( 'wp_logout', $user_id );

        $records = $this->tester->grabLogRecords();
        $this->assertCount( 1, $records );

        $extra = $records[0]->extra;
        $this->assertArrayHasKey( 'user', $extra );
        $this->assertEquals( $user_id, $extra['user']['id'] );
    }
}
