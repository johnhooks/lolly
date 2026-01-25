<?php

declare(strict_types=1);

namespace Tests\Wpunit\Listeners\UserListener;

use Lolly\Listeners\UserListener;
use lucatume\WPBrowser\TestCase\WPTestCase;
use Tests\Support\WpunitTester;

/**
 * @property WpunitTester $tester
 */
class RoleChangedTest extends WPTestCase {
    public function _before(): void {
        parent::_before();

        $this->tester->updateSettings(
            [
                'enabled'               => true,
                'wp_user_event_logging' => [ 'enabled' => true ],
            ]
        );

        add_action(
            'set_user_role',
            lolly()->callback( UserListener::class, 'on_role_changed' ),
            10,
            3
        );
    }

    public function _after(): void {
        remove_all_actions( 'set_user_role' );

        parent::_after();
    }

    public function testLogsRoleChange(): void {
        $user_id = self::factory()->user->create( [ 'role' => 'subscriber' ] );
        $user    = get_userdata( $user_id );

        $this->tester->fakeLogger();

        $user->set_role( 'editor' );

        $this->tester->seeLogMessage( 'User role changed.', 'info' );
    }

    public function testCapturesTargetUserIdAndRoles(): void {
        $user_id = self::factory()->user->create( [ 'role' => 'subscriber' ] );
        $user    = get_userdata( $user_id );

        $this->tester->fakeLogger();

        $user->set_role( 'author' );

        $records = $this->tester->grabLogRecords();
        $this->assertCount( 1, $records );

        $context = $records[0]->context;
        $this->assertArrayHasKey( 'target_user', $context );
        $this->assertIsArray( $context['target_user'] );
        $this->assertEquals( $user_id, $context['target_user']['id'] );
        $this->assertArrayHasKey( 'role', $context );
        $this->assertArrayHasKey( 'old_roles', $context );
        $this->assertEquals( 'author', $context['role'] );
        $this->assertContains( 'subscriber', $context['old_roles'] );
    }

    public function testCapturesActorInExtra(): void {
        $admin   = $this->tester->loginAsAdmin();
        $user_id = self::factory()->user->create( [ 'role' => 'subscriber' ] );
        $user    = get_userdata( $user_id );

        $this->tester->fakeLogger();

        $user->set_role( 'editor' );

        $records = $this->tester->grabLogRecords();
        $this->assertCount( 1, $records );

        $extra = $records[0]->extra;
        $this->assertArrayHasKey( 'user', $extra );
        $this->assertEquals( $admin->ID, $extra['user']['id'] );
    }
}
