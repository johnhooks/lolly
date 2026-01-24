<?php

declare(strict_types=1);

namespace Tests\Wpunit\Listeners;

use Lolly\Listeners\LogOnUserRoleRemoved;
use lucatume\WPBrowser\TestCase\WPTestCase;
use Tests\Support\WpunitTester;

/**
 * @property WpunitTester $tester
 */
class LogOnUserRoleRemovedTest extends WPTestCase {
    public function _before(): void {
        parent::_before();

        $this->tester->updateSettings(
            [
                'enabled'                       => true,
                'wp_user_event_logging_enabled' => true,
            ]
        );

        add_action(
            'remove_user_role',
            lolly()->callback( LogOnUserRoleRemoved::class, 'handle' ),
            10,
            2
        );
    }

    public function _after(): void {
        remove_all_actions( 'remove_user_role' );

        parent::_after();
    }

    public function testLogsRoleRemoved(): void {
        $user_id = self::factory()->user->create( [ 'role' => 'editor' ] );
        $user    = get_userdata( $user_id );
        $user->add_role( 'author' );

        $this->tester->fakeLogger();

        $user->remove_role( 'author' );

        $this->tester->seeLogMessage( 'User role removed.', 'info' );
    }

    public function testCapturesTargetUserIdAndRole(): void {
        $user_id = self::factory()->user->create( [ 'role' => 'editor' ] );
        $user    = get_userdata( $user_id );
        $user->add_role( 'contributor' );

        $this->tester->fakeLogger();

        $user->remove_role( 'contributor' );

        $records = $this->tester->grabLogRecords();
        $this->assertCount( 1, $records );

        $context = $records[0]->context;
        $this->assertArrayHasKey( 'target_user', $context );
        $this->assertIsArray( $context['target_user'] );
        $this->assertEquals( $user_id, $context['target_user']['id'] );
        $this->assertArrayHasKey( 'role', $context );
        $this->assertEquals( 'contributor', $context['role'] );
    }

    public function testCapturesActorInExtra(): void {
        $admin   = $this->tester->loginAsAdmin();
        $user_id = self::factory()->user->create( [ 'role' => 'editor' ] );
        $user    = get_userdata( $user_id );
        $user->add_role( 'author' );

        $this->tester->fakeLogger();

        $user->remove_role( 'author' );

        $records = $this->tester->grabLogRecords();
        $this->assertCount( 1, $records );

        $extra = $records[0]->extra;
        $this->assertArrayHasKey( 'user', $extra );
        $this->assertEquals( $admin->ID, $extra['user']['id'] );
    }
}
