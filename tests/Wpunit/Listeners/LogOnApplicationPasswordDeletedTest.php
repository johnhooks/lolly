<?php

declare(strict_types=1);

namespace Tests\Wpunit\Listeners;

use Lolly\Listeners\LogOnApplicationPasswordDeleted;
use lucatume\WPBrowser\TestCase\WPTestCase;
use Tests\Support\WpunitTester;

/**
 * @property WpunitTester $tester
 */
class LogOnApplicationPasswordDeletedTest extends WPTestCase {
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
            'wp_delete_application_password',
            lolly()->callback( LogOnApplicationPasswordDeleted::class, 'handle' ),
            10,
            2
        );
    }

    public function _after(): void {
        remove_all_actions( 'wp_delete_application_password' );

        parent::_after();
    }

    public function testLogsApplicationPasswordDeleted(): void {
        $user_id = self::factory()->user->create( [ 'role' => 'subscriber' ] );
        $item    = [
            'uuid' => wp_generate_uuid4(),
            'name' => 'Test App',
        ];

        $this->tester->fakeLogger();

        do_action( 'wp_delete_application_password', $user_id, $item );

        $this->tester->seeLogMessage( 'Application password deleted.', 'info' );
    }

    public function testCapturesTargetUserIdAndAppInfo(): void {
        $user_id = self::factory()->user->create( [ 'role' => 'editor' ] );
        $item    = [
            'uuid' => wp_generate_uuid4(),
            'name' => 'Deleted Application',
        ];

        $this->tester->fakeLogger();

        do_action( 'wp_delete_application_password', $user_id, $item );

        $records = $this->tester->grabLogRecords();
        $this->assertCount( 1, $records );

        $context = $records[0]->context;
        $this->assertArrayHasKey( 'target_user', $context );
        $this->assertIsArray( $context['target_user'] );
        $this->assertEquals( $user_id, $context['target_user']['id'] );
        $this->assertArrayHasKey( 'app_name', $context );
        $this->assertArrayHasKey( 'app_uuid', $context );
        $this->assertEquals( 'Deleted Application', $context['app_name'] );
    }

    public function testCapturesActorInExtra(): void {
        $admin   = $this->tester->loginAsAdmin();
        $user_id = self::factory()->user->create( [ 'role' => 'subscriber' ] );
        $item    = [
            'uuid' => wp_generate_uuid4(),
            'name' => 'Test App',
        ];

        $this->tester->fakeLogger();

        do_action( 'wp_delete_application_password', $user_id, $item );

        $records = $this->tester->grabLogRecords();
        $this->assertCount( 1, $records );

        $extra = $records[0]->extra;
        $this->assertArrayHasKey( 'user', $extra );
        $this->assertEquals( $admin->ID, $extra['user']['id'] );
    }
}
