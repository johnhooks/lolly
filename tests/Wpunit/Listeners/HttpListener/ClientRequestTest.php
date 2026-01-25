<?php

declare(strict_types=1);

namespace Tests\Wpunit\Listeners\HttpListener;

use Lolly\Listeners\HttpListener;
use lucatume\WPBrowser\TestCase\WPTestCase;
use Tests\Support\WpunitTester;
use WP_Error;

/**
 * @property WpunitTester $tester
 */
class ClientRequestTest extends WPTestCase {
    private const TEST_URL = 'https://api.example.com/test';

    public function _before(): void {
        parent::_before();

        $this->tester->updateSettings(
            [
                'enabled'                => true,
                'wp_http_client_logging' => [ 'enabled' => true ],
            ]
        );

        $this->tester->fakeLogger();

        add_action(
            'http_api_debug',
            lolly()->callback( HttpListener::class, 'on_client_request' ),
            999,
            5
        );
    }

    public function _after(): void {
        remove_all_actions( 'http_api_debug' );

        parent::_after();
    }

    public function testLogsSuccessfulHttpRequest(): void {
        $response = $this->buildResponse( 200, '{"success": true}' );

        do_action( 'http_api_debug', $response, 'response', 'Requests', $this->buildRequestArgs(), self::TEST_URL );

        $this->tester->seeLogMessage( 'HTTP client request.', 'info' );
    }

    public function testLogsHttpErrorResponseAtInfoLevel(): void {
        $response = $this->buildResponse( 500, '{"error": "Server error"}' );

        do_action( 'http_api_debug', $response, 'response', 'Requests', $this->buildRequestArgs(), self::TEST_URL );

        // HTTP 500 is not a WP_Error, so it logs at info level.
        // WP_Error (e.g., connection timeout) would log at error level.
        $this->tester->seeLogMessage( 'HTTP client request.', 'info' );
    }

    public function testLogsWpErrorAtErrorLevel(): void {
        $error = new WP_Error( 'http_request_failed', 'Connection timed out' );

        do_action( 'http_api_debug', $error, 'response', 'Requests', $this->buildRequestArgs(), self::TEST_URL );

        $this->tester->seeLogMessage( 'HTTP client request.', 'error' );
    }

    public function testIgnoresWpCronRequests(): void {
        $cronUrl  = 'https://example.com/wp-cron.php?doing_wp_cron=123';
        $response = $this->buildResponse( 200 );

        do_action( 'http_api_debug', $response, 'response', 'Requests', $this->buildRequestArgs(), $cronUrl );

        $this->tester->seeLogCount( 0 );
    }

    public function testIgnoresLoopbackRequests(): void {
        $siteUrl  = trailingslashit( get_site_url() );
        $response = $this->buildResponse( 200 );

        do_action( 'http_api_debug', $response, 'response', 'Requests', $this->buildRequestArgs(), $siteUrl );

        $this->tester->seeLogCount( 0 );
    }

    public function testRespectsWhitelistWhenEnabled(): void {
        $this->tester->updateSettings(
            [
                'enabled'                => true,
                'wp_http_client_logging' => [ 'enabled' => true ],
                'http_whitelist'         => [
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

        $response    = $this->buildResponse( 200, '{}' );
        $requestArgs = $this->buildRequestArgs();

        // Non-whitelisted host should not be logged.
        do_action( 'http_api_debug', $response, 'response', 'Requests', $requestArgs, 'https://blocked.example.com/api' );
        $this->tester->seeLogCount( 0 );

        // Whitelisted host should be logged.
        do_action( 'http_api_debug', $response, 'response', 'Requests', $requestArgs, 'https://allowed.example.com/api' );
        $this->tester->seeLogCount( 1 );
    }

    public function testLogsAllHostsWhenWhitelistDisabled(): void {
        $response = $this->buildResponse( 200, '{}' );

        do_action( 'http_api_debug', $response, 'response', 'Requests', $this->buildRequestArgs(), 'https://any.example.com/api' );

        $this->tester->seeLogCount( 1 );
    }

    public function testCapturesRequestContext(): void {
        $response    = $this->buildResponse( 200, '{"data": "test"}', [ 'Content-Type' => 'application/json' ] );
        $requestArgs = $this->buildRequestArgs( 'POST', [ 'body' => [ 'key' => 'value' ] ] );

        do_action( 'http_api_debug', $response, 'response', 'Requests', $requestArgs, self::TEST_URL );

        $records = $this->tester->grabLogRecords();
        $this->assertCount( 1, $records );

        $context = $records[0]->context;
        $this->assertArrayHasKey( 'wp_http_client', $context );
    }

    /**
     * Build a response array matching WordPress HTTP API format.
     *
     * @param int                   $status  HTTP status code.
     * @param string                $body    Response body.
     * @param array<string, string> $headers Response headers.
     *
     * @return array<string, mixed>
     */
    private function buildResponse( int $status = 200, string $body = '', array $headers = [] ): array {
        return [
            'response' => [
                'code'    => $status,
                'message' => get_status_header_desc( $status ),
            ],
            'body'     => $body,
            'headers'  => $headers,
            'cookies'  => [],
        ];
    }

    /**
     * Build request args matching WordPress HTTP API format.
     *
     * @param string               $method HTTP method.
     * @param array<string, mixed> $args   Additional request args.
     *
     * @return array<string, mixed>
     */
    private function buildRequestArgs( string $method = 'GET', array $args = [] ): array {
        return array_merge(
            [
                'method'  => $method,
                'headers' => [],
                'body'    => null,
            ],
            $args
        );
    }
}
