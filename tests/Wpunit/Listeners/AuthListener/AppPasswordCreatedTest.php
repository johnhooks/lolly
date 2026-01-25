<?php

declare(strict_types=1);

namespace Tests\Wpunit\Listeners\AuthListener;

use Lolly\Listeners\AuthListener;
use lucatume\WPBrowser\TestCase\WPTestCase;
use Tests\Support\WpunitTester;

/**
 * @property WpunitTester $tester
 */
class AppPasswordCreatedTest extends WPTestCase {
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
            'wp_create_application_password',
            lolly()->callback( AuthListener::class, 'on_app_password_created' ),
            10,
            3
        );
    }

    public function _after(): void {
        remove_all_actions( 'wp_create_application_password' );

        parent::_after();
    }

    public function testLogsApplicationPasswordCreated(): void {
        $user_id = self::factory()->user->create( [ 'role' => 'subscriber' ] );
        $item    = [
            'uuid' => wp_generate_uuid4(),
            'name' => 'Test App',
        ];

        $this->tester->fakeLogger();

        do_action( 'wp_create_application_password', $user_id, $item, 'unhashed_password' );

        $this->tester->seeLogMessage( 'Application password created.', 'info' );
    }

    public function testCapturesTargetUserIdAndAppInfo(): void {
        $user_id = self::factory()->user->create( [ 'role' => 'editor' ] );
        $item    = [
            'uuid' => wp_generate_uuid4(),
            'name' => 'My Test Application',
        ];

        $this->tester->fakeLogger();

        do_action( 'wp_create_application_password', $user_id, $item, 'unhashed_password' );

        $records = $this->tester->grabLogRecords();
        $this->assertCount( 1, $records );

        $context = $records[0]->context;
        $this->assertArrayHasKey( 'target_user', $context );
        $this->assertIsArray( $context['target_user'] );
        $this->assertEquals( $user_id, $context['target_user']['id'] );
        $this->assertArrayHasKey( 'app_name', $context );
        $this->assertArrayHasKey( 'app_uuid', $context );
        $this->assertEquals( 'My Test Application', $context['app_name'] );
    }

    public function testDoesNotLogUnhashedPassword(): void {
        $user_id = self::factory()->user->create( [ 'role' => 'subscriber' ] );
        $item    = [
            'uuid' => wp_generate_uuid4(),
            'name' => 'Test App',
        ];

        $this->tester->fakeLogger();

        do_action( 'wp_create_application_password', $user_id, $item, 'secret_password_value' );

        $records = $this->tester->grabLogRecords();
        $this->assertCount( 1, $records );

        $context = $records[0]->context;
        $this->assertArrayNotHasKey( 'password', $context );
        $this->assertArrayNotHasKey( 'new_password', $context );

        // Ensure the password isn't sneaking in anywhere.
        $serialized = serialize( $context );
        $this->assertStringNotContainsString( 'secret_password_value', $serialized );
    }

    public function testCapturesActorInExtra(): void {
        $admin   = $this->tester->loginAsAdmin();
        $user_id = self::factory()->user->create( [ 'role' => 'subscriber' ] );
        $item    = [
            'uuid' => wp_generate_uuid4(),
            'name' => 'Test App',
        ];

        $this->tester->fakeLogger();

        do_action( 'wp_create_application_password', $user_id, $item, 'unhashed_password' );

        $records = $this->tester->grabLogRecords();
        $this->assertCount( 1, $records );

        $extra = $records[0]->extra;
        $this->assertArrayHasKey( 'user', $extra );
        $this->assertEquals( $admin->ID, $extra['user']['id'] );
    }
}
