<?php

declare(strict_types=1);

namespace Tests\Wpunit\Listeners\UserListener;

use Lolly\Listeners\UserListener;
use lucatume\WPBrowser\TestCase\WPTestCase;
use Tests\Support\WpunitTester;

/**
 * @property WpunitTester $tester
 */
class DeletedTest extends WPTestCase {
    public function _before(): void {
        parent::_before();

        $this->tester->updateSettings(
            [
                'enabled'               => true,
                'wp_user_event_logging' => [ 'enabled' => true ],
            ]
        );

        add_action(
            'delete_user',
            lolly()->callback( UserListener::class, 'on_deleted' ),
            10,
            3
        );
    }

    public function _after(): void {
        remove_all_actions( 'delete_user' );

        parent::_after();
    }

    public function testLogsUserDeletion(): void {
        $user_id = self::factory()->user->create( [ 'role' => 'subscriber' ] );

        $this->tester->fakeLogger();

        wp_delete_user( $user_id );

        $this->tester->seeLogMessage( 'User deleted.', 'info' );
    }

    public function testCapturesTargetUserIdAndRoles(): void {
        $user_id = self::factory()->user->create( [ 'role' => 'editor' ] );

        $this->tester->fakeLogger();

        wp_delete_user( $user_id );

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
        $admin   = $this->tester->loginAsAdmin();
        $user_id = self::factory()->user->create( [ 'role' => 'subscriber' ] );

        $this->tester->fakeLogger();

        wp_delete_user( $user_id );

        $records = $this->tester->grabLogRecords();
        $this->assertCount( 1, $records );

        $extra = $records[0]->extra;
        $this->assertArrayHasKey( 'user', $extra );
        $this->assertEquals( $admin->ID, $extra['user']['id'] );
    }

    public function testCapturesReassignUserId(): void {
        $this->tester->loginAsAdmin();

        $user_id     = self::factory()->user->create( [ 'role' => 'author' ] );
        $reassign_id = self::factory()->user->create( [ 'role' => 'editor' ] );

        $this->tester->fakeLogger();

        wp_delete_user( $user_id, $reassign_id );

        $records = $this->tester->grabLogRecords();
        $this->assertCount( 1, $records );

        $context = $records[0]->context;
        $this->assertArrayHasKey( 'reassign_to', $context );
        $this->assertIsArray( $context['reassign_to'] );
        $this->assertEquals( $reassign_id, $context['reassign_to']['id'] );
    }
}
