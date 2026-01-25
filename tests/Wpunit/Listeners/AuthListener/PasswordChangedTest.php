<?php

declare(strict_types=1);

namespace Tests\Wpunit\Listeners\AuthListener;

use Lolly\Listeners\AuthListener;
use lucatume\WPBrowser\TestCase\WPTestCase;
use Tests\Support\WpunitTester;

/**
 * @property WpunitTester $tester
 */
class PasswordChangedTest extends WPTestCase {
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
            'after_password_reset',
            lolly()->callback( AuthListener::class, 'on_password_reset' ),
            10,
            1
        );

        add_action(
            'profile_update',
            lolly()->callback( AuthListener::class, 'on_profile_update' ),
            10,
            3
        );
    }

    public function _after(): void {
        remove_all_actions( 'after_password_reset' );
        remove_all_actions( 'profile_update' );

        parent::_after();
    }

    public function testLogsPasswordReset(): void {
        $user_id = self::factory()->user->create( [ 'role' => 'subscriber' ] );
        $user    = get_userdata( $user_id );

        $this->tester->fakeLogger();

        do_action( 'after_password_reset', $user );

        $this->tester->seeLogMessage( 'User password reset.', 'info' );
    }

    public function testLogsPasswordChange(): void {
        $user_id      = self::factory()->user->create( [ 'role' => 'subscriber' ] );
        $user         = get_userdata( $user_id );
        $old_userdata = clone $user;

        $this->tester->fakeLogger();

        do_action(
            'profile_update',
            $user_id,
            $old_userdata,
            [ 'user_pass' => 'newpassword123' ]
        );

        $this->tester->seeLogMessage( 'User password changed.', 'info' );
    }

    public function testDoesNotLogProfileUpdateWithoutPasswordChange(): void {
        $user_id      = self::factory()->user->create( [ 'role' => 'subscriber' ] );
        $user         = get_userdata( $user_id );
        $old_userdata = clone $user;

        $this->tester->fakeLogger();

        do_action(
            'profile_update',
            $user_id,
            $old_userdata,
            [ 'display_name' => 'New Display Name' ]
        );

        $records = $this->tester->grabLogRecords();
        $this->assertCount( 0, $records );
    }

    public function testCapturesTargetUserId(): void {
        $user_id = self::factory()->user->create( [ 'role' => 'editor' ] );
        $user    = get_userdata( $user_id );

        $this->tester->fakeLogger();

        do_action( 'after_password_reset', $user );

        $records = $this->tester->grabLogRecords();
        $this->assertCount( 1, $records );

        $context = $records[0]->context;
        $this->assertArrayHasKey( 'target_user', $context );
        $this->assertIsArray( $context['target_user'] );
        $this->assertEquals( $user_id, $context['target_user']['id'] );
    }

    public function testCapturesActorInExtra(): void {
        $admin   = $this->tester->loginAsAdmin();
        $user_id = self::factory()->user->create( [ 'role' => 'subscriber' ] );
        $user    = get_userdata( $user_id );

        $this->tester->fakeLogger();

        do_action( 'after_password_reset', $user );

        $records = $this->tester->grabLogRecords();
        $this->assertCount( 1, $records );

        $extra = $records[0]->extra;
        $this->assertArrayHasKey( 'user', $extra );
        $this->assertEquals( $admin->ID, $extra['user']['id'] );
    }
}
