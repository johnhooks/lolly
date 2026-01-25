<?php

declare(strict_types=1);

namespace Tests\Wpunit\Listeners\HttpListener;

use Lolly\Listeners\HttpListener;
use lucatume\WPBrowser\TestCase\WPTestCase;
use Tests\Support\WpunitTester;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * @property WpunitTester $tester
 */
class RestRequestTest extends WPTestCase {
    private const TEST_ROUTE = '/wp/v2/posts';

    /**
     * @var array<string, mixed> Original $_SERVER values to restore after tests.
     */
    private array $originalServer = [];

    public function _before(): void {
        parent::_before();

        $this->originalServer = $_SERVER;
        $this->setServerVars( 'example.com', '/wp-json' . self::TEST_ROUTE );

        $this->tester->updateSettings(
            [
                'enabled'         => true,
                'wp_rest_logging' => [ 'enabled' => true ],
            ]
        );

        $this->tester->fakeLogger();

        add_filter(
            'rest_post_dispatch',
            lolly()->callback( HttpListener::class, 'on_rest_request' ),
            999,
            3
        );
    }

    public function _after(): void {
        $_SERVER = $this->originalServer;

        remove_all_filters( 'rest_post_dispatch' );

        parent::_after();
    }

    public function testLogsSuccessfulRestRequest(): void {
        $request  = new WP_REST_Request( 'GET', self::TEST_ROUTE );
        $response = new WP_REST_Response( [ 'data' => 'test' ], 200 );

        apply_filters( 'rest_post_dispatch', $response, rest_get_server(), $request );

        $this->tester->seeLogMessage( 'HTTP REST request.', 'info' );
    }

    public function testLogsErrorResponseAtErrorLevel(): void {
        $request = new WP_REST_Request( 'GET', self::TEST_ROUTE );

        // Create error response with the structure WP_REST_Response::as_error() expects.
        $error    = new WP_Error( 'rest_not_found', 'Not found', [ 'status' => 404 ] );
        $response = rest_convert_error_to_response( $error );

        apply_filters( 'rest_post_dispatch', $response, rest_get_server(), $request );

        $this->tester->seeLogMessage( 'HTTP REST request.', 'error' );
    }

    public function testLogsWpErrorAtErrorLevel(): void {
        $request = new WP_REST_Request( 'GET', self::TEST_ROUTE );
        $error   = new WP_Error( 'rest_error', 'Something went wrong' );

        apply_filters( 'rest_post_dispatch', $error, rest_get_server(), $request );

        $this->tester->seeLogMessage( 'HTTP REST request.', 'error' );
    }

    public function testRespectsWhitelistWhenEnabled(): void {
        $this->tester->updateSettings(
            [
                'enabled'         => true,
                'wp_rest_logging' => [ 'enabled' => true ],
                'http_whitelist'  => [
                    'enabled' => true,
                    'rules'   => [
                        [
                            'host'  => 'allowed.example.com',
                            'paths' => [ [ 'path' => '*' ] ],
                        ],
                    ],
                ],
            ]
        );

        $request  = new WP_REST_Request( 'GET', self::TEST_ROUTE );
        $response = new WP_REST_Response( [ 'data' => 'test' ], 200 );
        $server   = rest_get_server();

        // Non-whitelisted host should not be logged.
        $this->setServerVars( 'blocked.example.com', '/wp-json' . self::TEST_ROUTE );
        apply_filters( 'rest_post_dispatch', $response, $server, $request );
        $this->tester->seeLogCount( 0 );

        // Whitelisted host should be logged.
        $this->setServerVars( 'allowed.example.com', '/wp-json' . self::TEST_ROUTE );
        apply_filters( 'rest_post_dispatch', $response, $server, $request );
        $this->tester->seeLogCount( 1 );
    }

    public function testLogsAllHostsWhenWhitelistDisabled(): void {
        $request  = new WP_REST_Request( 'GET', self::TEST_ROUTE );
        $response = new WP_REST_Response( [ 'data' => 'test' ], 200 );

        apply_filters( 'rest_post_dispatch', $response, rest_get_server(), $request );

        $this->tester->seeLogCount( 1 );
    }

    public function testCapturesRequestContext(): void {
        $request = new WP_REST_Request( 'POST', self::TEST_ROUTE );
        $request->set_body_params( [ 'title' => 'Test Post' ] );
        $response = new WP_REST_Response( [ 'id' => 1 ], 201 );

        apply_filters( 'rest_post_dispatch', $response, rest_get_server(), $request );

        $this->tester->seeLogMessage( 'HTTP REST request.', 'info' );
        $this->tester->seeLogCount( 1 );
    }

    /**
     * Set $_SERVER variables for the request URL.
     *
     * @param string $host       The host name.
     * @param string $requestUri The request URI.
     */
    private function setServerVars( string $host, string $requestUri ): void {
        $_SERVER['HTTP_HOST']   = $host;
        $_SERVER['REQUEST_URI'] = $requestUri;
        $_SERVER['HTTPS']       = 'on';
    }
}
