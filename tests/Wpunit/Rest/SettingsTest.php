<?php

declare(strict_types=1);

namespace Tests\Wpunit\Rest;

use Dozuki\Config\Config;
use lucatume\WPBrowser\TestCase\WPRestApiTestCase;
use WP_REST_Request;

class SettingsTest extends WPRestApiTestCase {
    private Config $config;

    public function setUp(): void {
        parent::setUp();

        $this->tester->login_as_admin();

        do_action( 'rest_api_init' );

        // Create a temporary log directory for testing.
        $log_dir = sys_get_temp_dir() . '/dozuki_test_logs';
        if ( ! is_dir( $log_dir ) ) {
            mkdir( $log_dir, 0755, true );
        }

        $this->config = new Config( $log_dir );
        $this->config->register_settings();
    }

    public function tearDown(): void {
        parent::tearDown();

        delete_option( 'dozuki_settings' );

        // Clean up temporary log directory.
        $log_dir = sys_get_temp_dir() . '/dozuki_test_logs';
        if ( is_dir( $log_dir ) ) {
            array_map( 'unlink', glob( "$log_dir/*" ) );
            rmdir( $log_dir );
        }

        $this->tester->logout();
    }

    public function testSettingsAreRegistered(): void {
        global $wp_rest_server;
        $routes = $wp_rest_server->get_routes();

        $this->assertArrayHasKey( '/wp/v2/settings', $routes );
        $this->assertNotEmpty( $routes['/wp/v2/settings'] );

        $registered_settings = get_registered_settings();
        $this->assertArrayHasKey( 'dozuki_settings', $registered_settings );
    }

    public function testNonAdminCannotAccessEndpoint(): void {
        $this->tester->logout();
        $this->tester->login_as_role( 'subscriber' );

        $request  = new WP_REST_Request( 'GET', '/wp/v2/settings' );
        $response = rest_do_request( $request );

        $this->assertEquals( 403, $response->get_status() );
    }

    public function testCanGetDefaultSettings(): void {
        $request  = new WP_REST_Request( 'GET', '/wp/v2/settings' );
        $response = rest_do_request( $request );

        $this->assertEquals( 200, $response->get_status() );

        $data = $response->get_data();

        $this->assertIsArray( $data );
        $this->assertArrayHasKey( 'dozuki_settings', $data );

        $dozuki_data = $data['dozuki_settings'];
        $this->assertIsArray( $dozuki_data );
        $this->assertArrayHasKey( 'enabled', $dozuki_data );
        $this->assertArrayHasKey( 'http_redactions_enabled', $dozuki_data );
        $this->assertArrayHasKey( 'http_redactions', $dozuki_data );
        $this->assertArrayHasKey( 'http_whitelist', $dozuki_data );

        $this->assertFalse( $dozuki_data['enabled'] );
        $this->assertTrue( $dozuki_data['http_redactions_enabled'] );
        $this->assertIsArray( $dozuki_data['http_redactions'] );
        $this->assertIsArray( $dozuki_data['http_whitelist'] );
    }

    public function testCanUpdateSettings(): void {
        $test_settings = [
            'dozuki_settings' => [
                'version'                        => 1,
                'enabled'                        => true,
                'wp_rest_logging_enabled'        => false,
                'wp_http_client_logging_enabled' => false,
                'http_redactions_enabled'        => false,
                'http_whitelist_enabled'         => true,
                'http_redactions'                => [
                    [
                        'host'  => 'example.com',
                        'paths' => [
                            [
                                'path'       => '/api/users',
                                'redactions' => [
                                    [
                                        'type'  => 'header',
                                        'value' => 'authorization',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'http_whitelist'                 => [
                    [
                        'host' => 'api.example.org',
                    ],
                ],
            ],
        ];

        $request = new WP_REST_Request( 'POST', '/wp/v2/settings' );
        $request->set_body_params( $test_settings );

        $response = rest_do_request( $request );

        $this->assertEquals( 200, $response->get_status() );

        $data = $response->get_data();
        $this->assertArrayHasKey( 'dozuki_settings', $data );

        $get_request  = new WP_REST_Request( 'GET', '/wp/v2/settings' );
        $get_response = rest_do_request( $get_request );

        $this->assertEquals( 200, $get_response->get_status() );

        $settings        = $get_response->get_data();
        $dozuki_settings = $settings['dozuki_settings'];

        $this->assertTrue( $dozuki_settings['enabled'] );
        $this->assertFalse( $dozuki_settings['wp_rest_logging_enabled'] );
        $this->assertFalse( $dozuki_settings['http_redactions_enabled'] );
        $this->assertTrue( $dozuki_settings['http_whitelist_enabled'] );
        $this->assertCount( 1, $dozuki_settings['http_redactions'] );
        $this->assertEquals( 'example.com', $dozuki_settings['http_redactions'][0]['host'] );
        $this->assertCount( 1, $dozuki_settings['http_whitelist'] );
        $this->assertEquals( 'api.example.org', $dozuki_settings['http_whitelist'][0]['host'] );
    }

    public function testSchemaIsProperlyDefined(): void {
        $registered_settings = get_registered_settings();
        $dozuki_setting      = $registered_settings['dozuki_settings'];

        $this->assertIsArray( $dozuki_setting );
        $this->assertArrayHasKey( 'show_in_rest', $dozuki_setting );
        $this->assertArrayHasKey( 'schema', $dozuki_setting['show_in_rest'] );

        $schema = $dozuki_setting['show_in_rest']['schema'];
        $this->assertIsArray( $schema );
        $this->assertArrayHasKey( 'properties', $schema );
        $this->assertArrayHasKey( 'enabled', $schema['properties'] );
        $this->assertArrayHasKey( 'http_redactions_enabled', $schema['properties'] );
        $this->assertArrayHasKey( 'http_redactions', $schema['properties'] );
        $this->assertArrayHasKey( 'http_whitelist', $schema['properties'] );

        $this->assertEquals( 'boolean', $schema['properties']['enabled']['type'] );
        $this->assertEquals( 'boolean', $schema['properties']['http_redactions_enabled']['type'] );
        $this->assertEquals( 'array', $schema['properties']['http_redactions']['type'] );
        $this->assertEquals( 'array', $schema['properties']['http_whitelist']['type'] );
    }
}
