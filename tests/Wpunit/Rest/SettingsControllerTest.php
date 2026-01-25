<?php

declare(strict_types=1);

namespace Tests\Wpunit\Rest;

use Lolly\Config\Config;
use lucatume\WPBrowser\TestCase\WPRestApiTestCase;
use WP_REST_Request;

/**
 * Tests for the custom /lolly/v1/settings endpoint.
 */
class SettingsControllerTest extends WPRestApiTestCase {

    public function setUp(): void {
        parent::setUp();

        do_action( 'rest_api_init' );
    }

    public function tearDown(): void {
        parent::tearDown();

        delete_option( Config::OPTION_SLUG );
        delete_transient( 'lolly_schema_settings_' . LOLLY_VERSION );

        $this->tester->logout();
    }

    public function testRouteIsRegistered(): void {
        global $wp_rest_server;
        $routes = $wp_rest_server->get_routes();

        $this->assertArrayHasKey( '/lolly/v1/settings', $routes );
    }

    public function testNonAdminCannotAccessEndpoint(): void {
        $this->tester->loginAsRole( 'subscriber' );

        $request  = new WP_REST_Request( 'GET', '/lolly/v1/settings' );
        $response = rest_do_request( $request );

        $this->assertEquals( 403, $response->get_status() );
    }

    public function testUnauthenticatedCannotAccessEndpoint(): void {
        $request  = new WP_REST_Request( 'GET', '/lolly/v1/settings' );
        $response = rest_do_request( $request );

        $this->assertEquals( 401, $response->get_status() );
    }

    public function testCanGetDefaultSettings(): void {
        $this->tester->loginAsAdmin();

        $request  = new WP_REST_Request( 'GET', '/lolly/v1/settings' );
        $response = rest_do_request( $request );

        $this->assertEquals( 200, $response->get_status() );

        $data = $response->get_data();

        $this->assertIsArray( $data );

        // Check all expected fields are present.
        $this->assertArrayHasKey( 'version', $data );
        $this->assertArrayHasKey( 'enabled', $data );
        $this->assertArrayHasKey( 'wp_rest_logging', $data );
        $this->assertArrayHasKey( 'wp_http_client_logging', $data );
        $this->assertArrayHasKey( 'wp_user_event_logging', $data );
        $this->assertArrayHasKey( 'wp_auth_logging', $data );
        $this->assertArrayHasKey( 'http_redactions', $data );
        $this->assertArrayHasKey( 'http_whitelist', $data );

        // Check defaults match schema.
        $this->assertEquals( 1, $data['version'] );
        $this->assertFalse( $data['enabled'] );

        // Check nested feature configs.
        $this->assertTrue( $data['wp_rest_logging']['enabled'] );
        $this->assertTrue( $data['wp_http_client_logging']['enabled'] );
        $this->assertTrue( $data['wp_user_event_logging']['enabled'] );
        $this->assertTrue( $data['http_redactions']['enabled'] );
        $this->assertFalse( $data['http_whitelist']['enabled'] );

        // Check nested auth config defaults.
        $auth_config = $data['wp_auth_logging'];
        $this->assertTrue( $auth_config['enabled'] );
        $this->assertTrue( $auth_config['login'] );
        $this->assertTrue( $auth_config['logout'] );
        $this->assertFalse( $auth_config['login_failed'] );
        $this->assertTrue( $auth_config['password_changed'] );
    }

    public function testDoesNotReturnRedactionRules(): void {
        $this->tester->loginAsAdmin();

        $request  = new WP_REST_Request( 'GET', '/lolly/v1/settings' );
        $response = rest_do_request( $request );

        $data = $response->get_data();

        // The rules arrays should NOT be in the response (they'll have their own endpoints).
        $this->assertArrayNotHasKey( 'rules', $data['http_redactions'] ?? [] );
        $this->assertArrayNotHasKey( 'rules', $data['http_whitelist'] ?? [] );
    }

    public function testCanUpdateSettings(): void {
        $this->tester->loginAsAdmin();

        $request = new WP_REST_Request( 'PUT', '/lolly/v1/settings' );
        $request->set_header( 'Content-Type', 'application/json' );
        $request->set_body(
            wp_json_encode(
                [
                    'enabled'         => true,
                    'wp_rest_logging' => [ 'enabled' => false ],
                ]
            )
        );

        $response = rest_do_request( $request );

        $this->assertEquals( 200, $response->get_status() );

        $data = $response->get_data();

        $this->assertTrue( $data['enabled'] );
        $this->assertFalse( $data['wp_rest_logging']['enabled'] );

        // Verify via GET.
        $get_request  = new WP_REST_Request( 'GET', '/lolly/v1/settings' );
        $get_response = rest_do_request( $get_request );
        $get_data     = $get_response->get_data();

        $this->assertTrue( $get_data['enabled'] );
        $this->assertFalse( $get_data['wp_rest_logging']['enabled'] );
    }

    public function testUpdatePreservesRedactionRulesInStorage(): void {
        $this->tester->loginAsAdmin();

        // First, save some redaction rules via the option directly.
        $initial = [
            'enabled'         => false,
            'http_redactions' => [
                'enabled' => true,
                'rules'   => [
                    [
                        'host'  => 'example.com',
                        'paths' => [
                            [
                                'path'       => '/api',
                                'redactions' => [
                                    [
                                        'type'  => 'header',
                                        'value' => 'auth',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        update_option( Config::OPTION_SLUG, $initial );

        // Update via our endpoint.
        $request = new WP_REST_Request( 'PUT', '/lolly/v1/settings' );
        $request->set_header( 'Content-Type', 'application/json' );
        $request->set_body( wp_json_encode( [ 'enabled' => true ] ) );

        rest_do_request( $request );

        // Verify redaction rules are preserved in storage.
        $saved = get_option( Config::OPTION_SLUG );

        $this->assertTrue( $saved['enabled'] );
        $this->assertArrayHasKey( 'http_redactions', $saved );
        $this->assertArrayHasKey( 'rules', $saved['http_redactions'] );
        $this->assertEquals( 'example.com', $saved['http_redactions']['rules'][0]['host'] );
    }

    public function testRejectsInvalidData(): void {
        $this->tester->loginAsAdmin();

        $request = new WP_REST_Request( 'PUT', '/lolly/v1/settings' );
        $request->set_header( 'Content-Type', 'application/json' );
        $request->set_body( wp_json_encode( [ 'enabled' => 'not-a-boolean' ] ) );

        $response = rest_do_request( $request );

        $this->assertEquals( 400, $response->get_status() );
    }

    public function testCanUpdateNestedAuthConfig(): void {
        $this->tester->loginAsAdmin();

        $request = new WP_REST_Request( 'PUT', '/lolly/v1/settings' );
        $request->set_header( 'Content-Type', 'application/json' );
        $request->set_body(
            wp_json_encode(
                [
                    'wp_auth_logging' => [
                        'enabled'      => true,
                        'login'        => false,
                        'logout'       => false,
                        'login_failed' => true,
                    ],
                ]
            )
        );

        $response = rest_do_request( $request );

        $this->assertEquals( 200, $response->get_status() );

        $data        = $response->get_data();
        $auth_config = $data['wp_auth_logging'];

        $this->assertTrue( $auth_config['enabled'] );
        $this->assertFalse( $auth_config['login'] );
        $this->assertFalse( $auth_config['logout'] );
        $this->assertTrue( $auth_config['login_failed'] );
    }

    public function testEmptyBodyDoesNotChangeSettings(): void {
        $this->tester->loginAsAdmin();

        // First set some values.
        update_option(
            Config::OPTION_SLUG,
            [
                'enabled'         => true,
                'version'         => 1,
                'wp_rest_logging' => [ 'enabled' => false ],
            ]
        );

        // Send empty update.
        $request = new WP_REST_Request( 'PUT', '/lolly/v1/settings' );
        $request->set_header( 'Content-Type', 'application/json' );
        $request->set_body( wp_json_encode( [] ) );

        $response = rest_do_request( $request );

        $this->assertEquals( 200, $response->get_status() );

        // Verify enabled is still true.
        $data = $response->get_data();
        $this->assertTrue( $data['enabled'] );
        $this->assertFalse( $data['wp_rest_logging']['enabled'] );
    }

    public function testSchemaExposedViaOptions(): void {
        $this->tester->loginAsAdmin();

        $request  = new WP_REST_Request( 'OPTIONS', '/lolly/v1/settings' );
        $response = rest_do_request( $request );

        $this->assertEquals( 200, $response->get_status() );

        $data = $response->get_data();

        $this->assertArrayHasKey( 'schema', $data );

        $schema = $data['schema'];
        $this->assertArrayHasKey( 'properties', $schema );
        $this->assertArrayHasKey( 'enabled', $schema['properties'] );
        $this->assertArrayHasKey( 'wp_auth_logging', $schema['properties'] );
        $this->assertArrayHasKey( 'wp_rest_logging', $schema['properties'] );
    }
}
