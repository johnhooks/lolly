<?php

declare(strict_types=1);

namespace Tests\Wpunit\Listeners;

use Lolly\Listeners\LogOnUserRoleAdded;
use lucatume\WPBrowser\TestCase\WPTestCase;
use Tests\Support\WpunitTester;

/**
 * @property WpunitTester $tester
 */
class LogOnUserRoleAddedTest extends WPTestCase {
    public function _before(): void {
        parent::_before();

        $this->tester->updateSettings(
            [
                'enabled'                       => true,
                'wp_user_event_logging_enabled' => true,
            ]
        );

        add_action(
            'add_user_role',
            lolly()->callback( LogOnUserRoleAdded::class, 'handle' ),
            10,
            2
        );
    }

    public function _after(): void {
        remove_all_actions( 'add_user_role' );

        parent::_after();
    }

    public function testLogsRoleAdded(): void {
        $user_id = self::factory()->user->create( [ 'role' => 'subscriber' ] );
        $user    = get_userdata( $user_id );

        $this->tester->fakeLogger();

        $user->add_role( 'editor' );

        $this->tester->seeLogMessage( 'User role added.', 'info' );
    }

    public function testCapturesTargetUserIdAndRole(): void {
        $user_id = self::factory()->user->create( [ 'role' => 'subscriber' ] );
        $user    = get_userdata( $user_id );

        $this->tester->fakeLogger();

        $user->add_role( 'author' );

        $records = $this->tester->grabLogRecords();
        $this->assertCount( 1, $records );

        $context = $records[0]->context;
        $this->assertArrayHasKey( 'target_user', $context );
        $this->assertIsArray( $context['target_user'] );
        $this->assertEquals( $user_id, $context['target_user']['id'] );
        $this->assertArrayHasKey( 'role', $context );
        $this->assertEquals( 'author', $context['role'] );
    }

    public function testCapturesActorInExtra(): void {
        $admin   = $this->tester->loginAsAdmin();
        $user_id = self::factory()->user->create( [ 'role' => 'subscriber' ] );
        $user    = get_userdata( $user_id );

        $this->tester->fakeLogger();

        $user->add_role( 'contributor' );

        $records = $this->tester->grabLogRecords();
        $this->assertCount( 1, $records );

        $extra = $records[0]->extra;
        $this->assertArrayHasKey( 'user', $extra );
        $this->assertEquals( $admin->ID, $extra['user']['id'] );
    }
}
