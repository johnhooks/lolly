<?php

declare(strict_types=1);

namespace Tests\Wpunit\Listeners\AuthListener;

use Lolly\Listeners\AuthListener;
use lucatume\WPBrowser\TestCase\WPTestCase;
use Tests\Support\WpunitTester;

/**
 * @property WpunitTester $tester
 */
class LoginTest extends WPTestCase {
    public function _before(): void {
        parent::_before();

        $this->tester->updateSettings(
            [
                'enabled'         => true,
                'wp_auth_logging' => [
                    'enabled'              => true,
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
            'wp_login',
            lolly()->callback( AuthListener::class, 'on_login' ),
            10,
            2
        );
    }

    public function _after(): void {
        remove_all_actions( 'wp_login' );

        parent::_after();
    }

    public function testLogsUserLogin(): void {
        $user_id = self::factory()->user->create( [ 'role' => 'subscriber' ] );
        $user    = get_userdata( $user_id );

        $this->tester->fakeLogger();

        do_action( 'wp_login', $user->user_login, $user );

        $this->tester->seeLogMessage( 'User logged in.', 'info' );
    }

    public function testCapturesTargetUserId(): void {
        $user_id = self::factory()->user->create( [ 'role' => 'editor' ] );
        $user    = get_userdata( $user_id );

        $this->tester->fakeLogger();

        do_action( 'wp_login', $user->user_login, $user );

        $records = $this->tester->grabLogRecords();
        $this->assertCount( 1, $records );

        $context = $records[0]->context;
        $this->assertArrayHasKey( 'target_user', $context );
        $this->assertIsArray( $context['target_user'] );
        $this->assertEquals( $user_id, $context['target_user']['id'] );
    }

    public function testCapturesActorInExtra(): void {
        $user_id = self::factory()->user->create( [ 'role' => 'subscriber' ] );
        $user    = get_userdata( $user_id );

        // Simulate the logged in user.
        wp_set_current_user( $user_id );

        $this->tester->fakeLogger();

        do_action( 'wp_login', $user->user_login, $user );

        $records = $this->tester->grabLogRecords();
        $this->assertCount( 1, $records );

        $extra = $records[0]->extra;
        $this->assertArrayHasKey( 'user', $extra );
        $this->assertEquals( $user_id, $extra['user']['id'] );
    }
}
