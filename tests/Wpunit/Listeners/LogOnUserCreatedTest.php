<?php

declare(strict_types=1);

namespace Tests\Wpunit\Listeners;

use Lolly\Listeners\LogOnUserCreated;
use lucatume\WPBrowser\TestCase\WPTestCase;
use Tests\Support\WpunitTester;

/**
 * @property WpunitTester $tester
 */
class LogOnUserCreatedTest extends WPTestCase {
    public function _before(): void {
        parent::_before();

        $this->tester->updateSettings(
            [
                'enabled'                       => true,
                'wp_user_event_logging_enabled' => true,
            ]
        );

        add_action(
            'user_register',
            lolly()->callback( LogOnUserCreated::class, 'handle' ),
            10,
            2
        );
    }

    public function _after(): void {
        remove_all_actions( 'user_register' );

        parent::_after();
    }

    public function testLogsUserCreation(): void {
        $this->tester->fakeLogger();

        wp_insert_user(
            [
                'user_login' => 'testuser',
                'user_pass'  => 'password123',
                'user_email' => 'test@example.com',
                'role'       => 'subscriber',
            ]
        );

        $this->tester->seeLogMessage( 'User created.', 'info' );
    }

    public function testCapturesTargetUserIdAndRoles(): void {
        $this->tester->fakeLogger();

        $user_id = wp_insert_user(
            [
                'user_login' => 'targetuser',
                'user_pass'  => 'password123',
                'user_email' => 'target@example.com',
                'role'       => 'editor',
            ]
        );

        $records = $this->tester->grabLogRecords();
        $this->assertCount( 1, $records );

        $context = $records[0]->context;
        $this->assertArrayHasKey( 'target_user', $context );
        $this->assertIsArray( $context['target_user'] );
        $this->assertEquals( $user_id, $context['target_user']['id'] );
        $this->assertArrayHasKey( 'roles', $context );
        $this->assertContains( 'editor', $context['roles'] );
    }

    public function testCapturesActorInExtra(): void {
        $admin = $this->tester->loginAsAdmin();

        $this->tester->fakeLogger();

        wp_insert_user(
            [
                'user_login' => 'newuser',
                'user_pass'  => 'password123',
                'user_email' => 'new@example.com',
                'role'       => 'subscriber',
            ]
        );

        $records = $this->tester->grabLogRecords();
        $this->assertCount( 1, $records );

        $extra = $records[0]->extra;
        $this->assertArrayHasKey( 'user', $extra );
        $this->assertEquals( $admin->ID, $extra['user']['id'] );
    }
}
