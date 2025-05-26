<?php

declare(strict_types=1);

namespace Tests\Wpunit\Lib\Services\Redactors\HttpMessage;

use Codeception\Attribute\DataProvider;
use Lolly\GuzzleHttp\Psr7\Request;
use Lolly\GuzzleHttp\Psr7\Response;
use Lolly\GuzzleHttp\Psr7\Utils;
use Lolly\Lib\Contracts\Redactors\Config;
use Lolly\Lib\Enums\HttpRedactionType;
use Lolly\Lib\Services\Redactors\HttpMessage\DefaultRedactor;
use Lolly\Lib\ValueObjects\Http\RedactionItem;
use Lolly\Psr\Http\Message\MessageInterface;
use Lolly\Psr\Http\Message\RequestInterface;
use Lolly\Psr\Http\Message\UriInterface;
use lucatume\WPBrowser\TestCase\WPTestCase;

class DefaultRedactorTest extends WpTestCase {
    private DefaultRedactor $redactor;
    private Config $config;

    public function setUp(): void {
        $this->config   = $this->createMock( Config::class );
        $this->redactor = new DefaultRedactor( $this->config );
    }

    public function test_it_returns_message_unchanged_when_no_redactions(): void {
        $url     = 'https://example.com/api/test';
        $request = new Request( 'GET', $url );

        $this->mock_http_redactions_enabled();
        $this->config->expects( $this->once() )
            ->method( 'get_http_redactions' )
            ->with( $this->isInstanceOf( UriInterface::class ) )
            ->willReturn( [] );

        $result = $this->redactor->redact( $url, $request );

        $this->assertInstanceOf( RequestInterface::class, $result );
        $this->assertEquals( $request->getUri(), $result->getUri() );
        $this->assertEquals( $request->getHeaders(), $result->getHeaders() );
        $this->assertEquals( '', $result->getBody()->getContents() );
    }

    public function test_it_accepts_string_url(): void {
        $url     = 'https://example.com/api/test';
        $request = new Request( 'GET', $url );

        $this->mock_http_redactions_enabled();
        $this->config->expects( $this->once() )
            ->method( 'get_http_redactions' )
            ->willReturn( [] );

        $result = $this->redactor->redact( $url, $request );

        $this->assertInstanceOf( RequestInterface::class, $result );
    }

    public function test_it_accepts_uri_interface(): void {
        $uri     = Utils::uriFor( 'https://example.com/api/test' );
        $request = new Request( 'GET', $uri );

        $this->mock_http_redactions_enabled();
        $this->config->expects( $this->once() )
            ->method( 'get_http_redactions' )
            ->willReturn( [] );

        $result = $this->redactor->redact( $uri, $request );

        $this->assertInstanceOf( RequestInterface::class, $result );
    }

    #[DataProvider( 'query_redaction_provider' )]
    public function test_it_redacts_query_parameters( string $url, array $redactions, string $expected_query ): void {
        $request = new Request( 'GET', $url );

        $this->mock_http_redactions_enabled();
        $this->config->expects( $this->once() )
            ->method( 'get_http_redactions' )
            ->willReturn( $redactions );

        $result = $this->redactor->redact( $url, $request );

        $this->assertEquals( $expected_query, $result->getUri()->getQuery() );
    }

    #[DataProvider( 'header_redaction_provider' )]
    public function test_it_redacts_headers( array $headers, array $redactions, array $expected_headers ): void {
        $url     = 'https://example.com/api/test';
        $request = new Request( 'GET', $url, $headers );

        $this->mock_http_redactions_enabled();
        $this->config->expects( $this->once() )
            ->method( 'get_http_redactions' )
            ->willReturn( $redactions );

        $result = $this->redactor->redact( $url, $request );

        $this->assertEquals( $expected_headers, $result->getHeaders() );
    }

    #[DataProvider( 'body_redaction_provider' )]
    public function test_it_redacts_request_body( string $content_type, string $body, array $redactions, string $expected_body ): void {
        $url     = 'https://example.com/api/test';
        $headers = [ 'Content-Type' => $content_type ];
        $request = new Request( 'POST', $url, $headers, $body );

        $this->mock_http_redactions_enabled();
        $this->config->expects( $this->once() )
            ->method( 'get_http_redactions' )
            ->willReturn( $redactions );

        $result = $this->redactor->redact( $url, $request );

        $this->assertEquals( $expected_body, $result->getBody()->getContents() );
    }

    #[DataProvider( 'response_redaction_provider' )]
    public function test_it_redacts_response_body( string $content_type, string $body, array $redactions, string $expected_body ): void {
        $url      = 'https://example.com/api/test';
        $headers  = [ 'Content-Type' => $content_type ];
        $response = new Response( 200, $headers, $body );

        $this->mock_http_redactions_enabled();
        $this->config->expects( $this->once() )
            ->method( 'get_http_redactions' )
            ->willReturn( $redactions );

        $result = $this->redactor->redact( $url, $response );

        $this->assertEquals( $expected_body, $result->getBody()->getContents() );
    }

    public function test_it_handles_invalid_json(): void {
        $url     = 'https://example.com/api/test';
        $headers = [ 'Content-Type' => 'application/json' ];
        $body    = '{"invalid": json}';
        $request = new Request( 'POST', $url, $headers, $body );

        $this->mock_http_redactions_enabled();
        $redactions = [ new RedactionItem( HttpRedactionType::Request, 'password' ) ];
        $this->config->expects( $this->once() )
            ->method( 'get_http_redactions' )
            ->willReturn( $redactions );

        $result = $this->redactor->redact( $url, $request );

        $this->assertEquals( '"JSON decode error: Syntax error"', $result->getBody()->getContents() );
    }

    public function test_it_returns_generic_message_unchanged(): void {
        $url     = 'https://example.com/api/test';
        $message = $this->createMock( MessageInterface::class );

        $this->mock_http_redactions_enabled();
        $this->config->expects( $this->once() )
            ->method( 'get_http_redactions' )
            ->willReturn( [] );

        $result = $this->redactor->redact( $url, $message );

        $this->assertSame( $message, $result );
    }

    #[DataProvider( 'always_redaction_provider' )]
    public function test_it_applies_always_redaction_to_all_types( string $url, array $headers, string $body, array $redactions, array $expected ): void {
        $request = new Request( 'POST', $url, $headers, $body );

        $this->mock_http_redactions_enabled();
        $this->config->expects( $this->once() )
            ->method( 'get_http_redactions' )
            ->willReturn( $redactions );

        $result = $this->redactor->redact( $url, $request );

        $this->assertEquals( $expected['query'], $result->getUri()->getQuery() );
        $this->assertEquals( $expected['headers'], $result->getHeaders() );
        $this->assertEquals( $expected['body'], $result->getBody()->getContents() );
    }

    #[DataProvider( 'nested_json_provider' )]
    public function test_it_redacts_nested_json_objects( string $body, array $redactions, string $expected_body ): void {
        $url     = 'https://example.com/api/test';
        $headers = [ 'Content-Type' => 'application/json' ];
        $request = new Request( 'POST', $url, $headers, $body );

        $this->mock_http_redactions_enabled();
        $this->config->expects( $this->once() )
            ->method( 'get_http_redactions' )
            ->willReturn( $redactions );

        $result = $this->redactor->redact( $url, $request );

        $this->assertEquals( $expected_body, $result->getBody()->getContents() );
    }

    #[DataProvider( 'json_array_provider' )]
    public function test_it_handles_json_arrays( string $body, array $redactions, string $expected_body ): void {
        $url     = 'https://example.com/api/test';
        $headers = [ 'Content-Type' => 'application/json' ];
        $request = new Request( 'POST', $url, $headers, $body );

        $this->mock_http_redactions_enabled();
        $this->config->expects( $this->once() )
            ->method( 'get_http_redactions' )
            ->willReturn( $redactions );

        $result = $this->redactor->redact( $url, $request );

        $this->assertEquals( $expected_body, $result->getBody()->getContents() );
    }

    public function test_it_handles_json_encode_errors(): void {
        $url     = 'https://example.com/api/test';
        $headers = [ 'Content-Type' => 'application/json' ];
        // Create a body with invalid UTF-8 sequence that will cause JSON encode to fail
        $body    = '{"valid": "' . "\xc3\x28" . '"}';
        $request = new Request( 'POST', $url, $headers, $body );

        $this->mock_http_redactions_enabled();
        $redactions = [ new RedactionItem( HttpRedactionType::Request, 'valid' ) ];
        $this->config->expects( $this->once() )
            ->method( 'get_http_redactions' )
            ->willReturn( $redactions );

        $result = $this->redactor->redact( $url, $request );

        // When JSON decode fails, it returns an error message
        $this->assertEquals( '"JSON decode error: Malformed UTF-8 characters, possibly incorrectly encoded"', $result->getBody()->getContents() );
    }

    #[DataProvider( 'empty_body_provider' )]
    public function test_it_handles_empty_and_null_bodies( string $content_type, string $body, array $redactions, string $expected_body ): void {
        $url     = 'https://example.com/api/test';
        $headers = [ 'Content-Type' => $content_type ];
        $request = new Request( 'POST', $url, $headers, $body );

        $this->mock_http_redactions_enabled();
        $this->config->expects( $this->once() )
            ->method( 'get_http_redactions' )
            ->willReturn( $redactions );

        $result = $this->redactor->redact( $url, $request );

        $this->assertEquals( $expected_body, $result->getBody()->getContents() );
    }

    #[DataProvider( 'multiple_redactions_provider' )]
    public function test_it_applies_multiple_redactions_of_same_type( string $body, array $redactions, string $expected_body ): void {
        $url     = 'https://example.com/api/test';
        $headers = [ 'Content-Type' => 'application/json' ];
        $request = new Request( 'POST', $url, $headers, $body );

        $this->mock_http_redactions_enabled();
        $this->config->expects( $this->once() )
            ->method( 'get_http_redactions' )
            ->willReturn( $redactions );

        $result = $this->redactor->redact( $url, $request );

        $this->assertEquals( $expected_body, $result->getBody()->getContents() );
    }

    #[DataProvider( 'header_case_sensitivity_provider' )]
    public function test_it_handles_header_case_insensitivity( array $headers, array $redactions, array $expected_headers ): void {
        $url     = 'https://example.com/api/test';
        $request = new Request( 'GET', $url, $headers );

        $this->mock_http_redactions_enabled();
        $this->config->expects( $this->once() )
            ->method( 'get_http_redactions' )
            ->willReturn( $redactions );

        $result = $this->redactor->redact( $url, $request );

        $this->assertEquals( $expected_headers, $result->getHeaders() );
    }

    #[DataProvider( 'query_no_values_provider' )]
    public function test_it_handles_query_parameters_with_no_values( string $url, array $redactions, string $expected_query ): void {
        $request = new Request( 'GET', $url );

        $this->mock_http_redactions_enabled();
        $this->config->expects( $this->once() )
            ->method( 'get_http_redactions' )
            ->willReturn( $redactions );

        $result = $this->redactor->redact( $url, $request );

        $this->assertEquals( $expected_query, $result->getUri()->getQuery() );
    }

    #[DataProvider( 'query_special_chars_provider' )]
    public function test_it_handles_query_parameters_with_special_characters( string $url, array $redactions, string $expected_query ): void {
        $request = new Request( 'GET', $url );

        $this->mock_http_redactions_enabled();
        $this->config->expects( $this->once() )
            ->method( 'get_http_redactions' )
            ->willReturn( $redactions );

        $result = $this->redactor->redact( $url, $request );

        $this->assertEquals( $expected_query, $result->getUri()->getQuery() );
    }

    #[DataProvider( 'html_xml_content_provider' )]
    public function test_it_does_not_redact_html_and_xml_content( string $content_type, string $body, array $redactions, string $expected_body ): void {
        $url     = 'https://example.com/api/test';
        $headers = [ 'Content-Type' => $content_type ];
        $request = new Request( 'POST', $url, $headers, $body );

        $this->mock_http_redactions_enabled();
        $this->config->expects( $this->once() )
            ->method( 'get_http_redactions' )
            ->willReturn( $redactions );

        $result = $this->redactor->redact( $url, $request );

        $this->assertEquals( $expected_body, $result->getBody()->getContents() );
    }

    #[DataProvider( 'url_edge_cases_provider' )]
    public function test_it_handles_url_edge_cases( string $url, array $redactions, string $expected_query ): void {
        $request = new Request( 'GET', $url );

        $this->mock_http_redactions_enabled();
        $this->config->expects( $this->once() )
            ->method( 'get_http_redactions' )
            ->willReturn( $redactions );

        $result = $this->redactor->redact( $url, $request );

        $this->assertEquals( $expected_query, $result->getUri()->getQuery() );
    }

    #[DataProvider( 'response_header_redaction_provider' )]
    public function test_it_redacts_response_headers( array $headers, array $redactions, array $expected_headers ): void {
        $url      = 'https://example.com/api/test';
        $response = new Response( 200, $headers, 'response body' );

        $this->mock_http_redactions_enabled();
        $this->config->expects( $this->once() )
            ->method( 'get_http_redactions' )
            ->willReturn( $redactions );

        $result = $this->redactor->redact( $url, $response );

        $this->assertEquals( $expected_headers, $result->getHeaders() );
    }

    #[DataProvider( 'mixed_redaction_types_provider' )]
    public function test_it_handles_mixed_redaction_types( string $url, array $headers, string $body, array $redactions, array $expected ): void {
        $request = new Request( 'POST', $url, $headers, $body );

        $this->mock_http_redactions_enabled();
        $this->config->expects( $this->once() )
            ->method( 'get_http_redactions' )
            ->willReturn( $redactions );

        $result = $this->redactor->redact( $url, $request );

        $this->assertEquals( $expected['query'], $result->getUri()->getQuery() );
        $this->assertEquals( $expected['headers'], $result->getHeaders() );
        $this->assertEquals( $expected['body'], $result->getBody()->getContents() );
    }

    #[DataProvider( 'additional_redactions_provider' )]
    public function test_it_accepts_additional_redactions( string $url, array $headers, string $body, array $config_redactions, array $additional_redactions, array $expected ): void {
        $request = new Request( 'POST', $url, $headers, $body );

        $this->mock_http_redactions_enabled();
        $this->config->expects( $this->once() )
            ->method( 'get_http_redactions' )
            ->willReturn( $config_redactions );

        $result = $this->redactor->redact( $url, $request, $additional_redactions );

        $this->assertEquals( $expected['query'], $result->getUri()->getQuery() );
        $this->assertEquals( $expected['headers'], $result->getHeaders() );
        $this->assertEquals( $expected['body'], $result->getBody()->getContents() );
    }

    public function test_it_works_with_empty_additional_redactions(): void {
        $url     = 'https://example.com/api/test?param=value';
        $headers = [ 'Authorization' => 'Bearer token' ];
        $body    = '{"data":"content"}';
        $request = new Request( 'POST', $url, $headers, $body );

        $this->mock_http_redactions_enabled();
        $config_redactions = [ new RedactionItem( HttpRedactionType::Query, 'param' ) ];
        $this->config->expects( $this->once() )
            ->method( 'get_http_redactions' )
            ->willReturn( $config_redactions );

        $result = $this->redactor->redact( $url, $request, [] );

        $this->assertEquals( 'param=redacted', $result->getUri()->getQuery() );
        $this->assertEquals( '{"data":"content"}', $result->getBody()->getContents() );
    }

    public function test_it_works_with_only_additional_redactions(): void {
        $url     = 'https://example.com/api/test?param=value';
        $headers = [
            'Authorization' => 'Bearer token',
            'Content-Type'  => 'application/json',
        ];

        $body    = '{"data":"content"}';
        $request = new Request( 'POST', $url, $headers, $body );

        $this->mock_http_redactions_enabled();
        $this->config->expects( $this->once() )
            ->method( 'get_http_redactions' )
            ->willReturn( [] );

        $additional_redactions = [ new RedactionItem( HttpRedactionType::Request, 'data' ) ];
        $result                = $this->redactor->redact( $url, $request, $additional_redactions );

        $this->assertEquals( 'param=value', $result->getUri()->getQuery() );
        $this->assertEquals( '{"data":"redacted"}', $result->getBody()->getContents() );
    }

    #[DataProvider( 'redaction_precedence_provider' )]
    public function test_it_handles_redaction_precedence_correctly( string $url, array $headers, string $body, array $config_redactions, array $additional_redactions, array $expected ): void {
        $request = new Request( 'POST', $url, $headers, $body );

        $this->mock_http_redactions_enabled();
        $this->config->expects( $this->once() )
            ->method( 'get_http_redactions' )
            ->willReturn( $config_redactions );

        $result = $this->redactor->redact( $url, $request, $additional_redactions );

        $this->assertEquals( $expected['query'], $result->getUri()->getQuery() );
        $this->assertEquals( $expected['headers'], $result->getHeaders() );
        $this->assertEquals( $expected['body'], $result->getBody()->getContents() );
    }

    #[DataProvider( 'additional_redaction_types_provider' )]
    public function test_it_handles_additional_redactions_for_all_types( string $url, array $headers, string $body, array $additional_redactions, array $expected ): void {
        $request = new Request( 'POST', $url, $headers, $body );

        $this->mock_http_redactions_enabled();
        $this->config->expects( $this->once() )
            ->method( 'get_http_redactions' )
            ->willReturn( [] );

        $result = $this->redactor->redact( $url, $request, $additional_redactions );

        $this->assertEquals( $expected['query'], $result->getUri()->getQuery() );
        $this->assertEquals( $expected['headers'], $result->getHeaders() );
        $this->assertEquals( $expected['body'], $result->getBody()->getContents() );
    }

    public function test_it_handles_additional_redactions_with_response(): void {
        $url      = 'https://example.com/api/test';
        $headers  = [
            'Set-Cookie'   => 'session=abc123',
            'Content-Type' => 'application/json',
        ];
        $body     = '{"token":"secret","data":"public"}';
        $response = new Response( 200, $headers, $body );

        $this->mock_http_redactions_enabled();
        $this->config->expects( $this->once() )
            ->method( 'get_http_redactions' )
            ->willReturn( [] );

        $additional_redactions = [
            new RedactionItem( HttpRedactionType::Header, 'Set-Cookie' ),
            new RedactionItem( HttpRedactionType::Response, 'token' ),
        ];

        $result = $this->redactor->redact( $url, $response, $additional_redactions );

        $expected_headers = [
            'Set-Cookie'   => [ 'redacted' ],
            'Content-Type' => [ 'application/json' ],
        ];

        $this->assertEquals( $expected_headers, $result->getHeaders() );
        $this->assertEquals( '{"token":"redacted","data":"public"}', $result->getBody()->getContents() );
    }

    protected function query_redaction_provider(): iterable {
        return [
            'no_redactions'                   => [
                'https://example.com/api/test?param1=value1&param2=value2&sensitive=secret',
                [],
                'param1=value1&param2=value2&sensitive=secret',
            ],
            'redact_specific_query_parameter' => [
                'https://example.com/api/test?param1=value1&param2=value2&sensitive=secret',
                [ new RedactionItem( HttpRedactionType::Query, 'sensitive' ) ],
                'param1=value1&param2=value2&sensitive=redacted',
            ],
            'redact_all_query_parameters'     => [
                'https://example.com/api/test?param1=value1&param2=value2&sensitive=secret',
                [ new RedactionItem( HttpRedactionType::Query, '*' ) ],
                'redacted=1',
            ],
            'remove_all_query_parameters'     => [
                'https://example.com/api/test?param1=value1&param2=value2&sensitive=secret',
                [ new RedactionItem( HttpRedactionType::Query, '*', true ) ],
                '',
            ],
        ];
    }

    protected function header_redaction_provider(): iterable {
        return [
            'no_redactions'          => [
                [
                    'Authorization' => 'Bearer secret-token',
                    'Content-Type'  => 'application/json',
                ],
                [],
                [
                    'Host'          => [ 'example.com' ],
                    'Authorization' => [ 'Bearer secret-token' ],
                    'Content-Type'  => [ 'application/json' ],
                ],
            ],
            'redact_specific_header' => [
                [
                    'Authorization' => 'Bearer secret-token',
                    'Content-Type'  => 'application/json',
                ],
                [ new RedactionItem( HttpRedactionType::Header, 'Authorization' ) ],
                [
                    'Host'          => [ 'example.com' ],
                    'Authorization' => [ 'redacted' ],
                    'Content-Type'  => [ 'application/json' ],
                ],
            ],
            'redact_all_headers'     => [
                [
                    'Authorization' => 'Bearer secret-token',
                    'Content-Type'  => 'application/json',
                ],
                [ new RedactionItem( HttpRedactionType::Header, '*' ) ],
                [
                    'Host'          => [ 'redacted' ],
                    'Authorization' => [ 'redacted' ],
                    'Content-Type'  => [ 'redacted' ],
                ],
            ],
            'remove_specific_header' => [
                [
                    'Authorization' => 'Bearer secret-token',
                    'Content-Type'  => 'application/json',
                ],
                [ new RedactionItem( HttpRedactionType::Header, 'Authorization', true ) ],
                [
                    'Host'         => [ 'example.com' ],
                    'Content-Type' => [ 'application/json' ],
                ],
            ],
        ];
    }

    protected function body_redaction_provider(): iterable {
        return [
            'no_redactions_json'                        => [
                'application/json',
                '{"username":"user","password":"secret"}',
                [],
                '{"username":"user","password":"secret"}',
            ],
            'redact_specific_json_key'                  => [
                'application/json',
                '{"username":"user","password":"secret"}',
                [ new RedactionItem( HttpRedactionType::Request, 'password' ) ],
                '{"username":"user","password":"redacted"}',
            ],
            'redact_all_json_content'                   => [
                'application/json',
                '{"username":"user","password":"secret"}',
                [ new RedactionItem( HttpRedactionType::Request, '*' ) ],
                '"redacted"',
            ],
            'remove_all_json_content'                   => [
                'application/json',
                '{"username":"user","password":"secret"}',
                [ new RedactionItem( HttpRedactionType::Request, '*', true ) ],
                '',
            ],
            'redact_form_encoded_body'                  => [
                'application/x-www-form-urlencoded',
                'username=user&password=secret',
                [ new RedactionItem( HttpRedactionType::Request, 'password' ) ],
                'username=user&password=redacted',
            ],
            'redact_all_form_encoded_content'           => [
                'application/x-www-form-urlencoded',
                'username=user&password=secret',
                [ new RedactionItem( HttpRedactionType::Request, '*' ) ],
                'redacted=1',
            ],
            'non_json_non_form_content_is_not_redacted' => [
                'text/plain',
                'This is plain text content',
                [ new RedactionItem( HttpRedactionType::Request, 'anything' ) ],
                'This is plain text content',
            ],
            'non_json_non_form_content_is_redacted'     => [
                'text/plain',
                'This is plain text content',
                [ new RedactionItem( HttpRedactionType::Request, '*' ) ],
                'redacted',
            ],
        ];
    }

    protected function response_redaction_provider(): iterable {
        return [
            'no_redactions_response_json'       => [
                'application/json',
                '{"user_id":123,"token":"secret-token","public_data":"visible"}',
                [],
                '{"user_id":123,"token":"secret-token","public_data":"visible"}',
            ],
            'redact_specific_response_json_key' => [
                'application/json',
                '{"user_id":123,"token":"secret-token","public_data":"visible"}',
                [ new RedactionItem( HttpRedactionType::Response, 'token' ) ],
                '{"user_id":123,"token":"redacted","public_data":"visible"}',
            ],
            'redact_all_response_json_content'  => [
                'application/json',
                '{"user_id":123,"token":"secret-token","public_data":"visible"}',
                [ new RedactionItem( HttpRedactionType::Response, '*' ) ],
                '"redacted"',
            ],
        ];
    }

    protected function always_redaction_provider(): iterable {
        return [
            'always_redaction_affects_all'             => [
                'https://example.com/api/test?secret=value',
                [
                    'Authorization' => 'Bearer token',
                    'Content-Type'  => 'application/json',
                ],
                '{"password":"secret"}',
                [ new RedactionItem( HttpRedactionType::Always, 'secret' ) ],
                [
                    'query'   => 'secret=redacted',
                    'headers' => [
                        'Host'          => [ 'example.com' ],
                        'Authorization' => [ 'Bearer token' ],
                        'Content-Type'  => [ 'application/json' ],
                    ],
                    'body'    => '{"password":"secret"}',
                ],
            ],
            'always_redacts_specific_field_everywhere' => [
                'https://example.com/api/test?data=value',
                [
                    'Authorization' => 'Bearer token',
                    'Content-Type'  => 'application/json',
                ],
                '{"data":"content"}',
                [ new RedactionItem( HttpRedactionType::Always, 'data' ) ],
                [
                    'query'   => 'data=redacted',
                    'headers' => [
                        'Host'          => [ 'example.com' ],
                        'Authorization' => [ 'Bearer token' ],
                        'Content-Type'  => [ 'application/json' ],
                    ],
                    'body'    => '{"data":"redacted"}',
                ],
            ],
        ];
    }

    protected function nested_json_provider(): iterable {
        return [
            'redact_nested_object_key'    => [
                '{"user":{"profile":{"password":"secret","email":"test@example.com"}}}',
                [ new RedactionItem( HttpRedactionType::Request, 'password' ) ],
                '{"user":{"profile":{"password":"redacted","email":"test@example.com"}}}',
            ],
            'redact_deeply_nested_key'    => [
                '{"level1":{"level2":{"level3":{"secret":"hidden","public":"visible"}}}}',
                [ new RedactionItem( HttpRedactionType::Request, 'secret' ) ],
                '{"level1":{"level2":{"level3":{"secret":"redacted","public":"visible"}}}}',
            ],
            'redact_multiple_nested_keys' => [
                '{"auth":{"token":"secret"},"user":{"password":"hidden"}}',
                [
                    new RedactionItem( HttpRedactionType::Request, 'token' ),
                    new RedactionItem( HttpRedactionType::Request, 'password' ),
                ],
                '{"auth":{"token":"redacted"},"user":{"password":"redacted"}}',
            ],
        ];
    }

    protected function json_array_provider(): iterable {
        return [
            'array_of_objects_with_redaction' => [
                '[{"id":1,"password":"secret1"},{"id":2,"password":"secret2"}]',
                [ new RedactionItem( HttpRedactionType::Request, 'password' ) ],
                '[{"id":1,"password":"redacted"},{"id":2,"password":"redacted"}]',
            ],
            'simple_array_no_redaction'       => [
                '["item1","item2","item3"]',
                [ new RedactionItem( HttpRedactionType::Request, 'nonexistent' ) ],
                '["item1","item2","item3"]',
            ],
            'mixed_array_and_object'          => [
                '{"items":["public"],"secrets":{"password":"hidden"}}',
                [ new RedactionItem( HttpRedactionType::Request, 'password' ) ],
                '{"items":["public"],"secrets":{"password":"redacted"}}',
            ],
        ];
    }

    protected function empty_body_provider(): iterable {
        return [
            'empty_json_body_skipped' => [
                'application/json',
                '',
                [ new RedactionItem( HttpRedactionType::Request, 'anything' ) ],
                '',
            ],
            'empty_form_body'         => [
                'application/x-www-form-urlencoded',
                '',
                [ new RedactionItem( HttpRedactionType::Request, 'anything' ) ],
                '',
            ],
            'whitespace_only_body'    => [
                'application/json',
                '   ',
                [ new RedactionItem( HttpRedactionType::Request, 'anything' ) ],
                '"JSON decode error: Syntax error"',
            ],
        ];
    }

    protected function multiple_redactions_provider(): iterable {
        return [
            'multiple_json_keys'       => [
                '{"username":"user","password":"secret","token":"abc123","public":"visible"}',
                [
                    new RedactionItem( HttpRedactionType::Request, 'password' ),
                    new RedactionItem( HttpRedactionType::Request, 'token' ),
                ],
                '{"username":"user","password":"redacted","token":"redacted","public":"visible"}',
            ],
            'mix_of_redact_and_remove' => [
                '{"keep":"this","redact":"this","remove":"this"}',
                [
                    new RedactionItem( HttpRedactionType::Request, 'redact' ),
                    new RedactionItem( HttpRedactionType::Request, 'remove', true ),
                ],
                '{"keep":"this","redact":"redacted"}',
            ],
        ];
    }

    protected function header_case_sensitivity_provider(): iterable {
        return [
            'lowercase_header_name_matches_uppercase' => [
                [ 'AUTHORIZATION' => 'Bearer token' ],
                [ new RedactionItem( HttpRedactionType::Header, 'authorization' ) ],
                [
                    'Host'          => [ 'example.com' ],
                    'AUTHORIZATION' => [ 'redacted' ],
                ],
            ],
            'uppercase_header_name_matches_lowercase' => [
                [ 'authorization' => 'Bearer token' ],
                [ new RedactionItem( HttpRedactionType::Header, 'AUTHORIZATION' ) ],
                [
                    'Host'          => [ 'example.com' ],
                    'authorization' => [ 'redacted' ],
                ],
            ],
            'mixed_case_header_matching'              => [
                [ 'Content-Type' => 'application/json' ],
                [ new RedactionItem( HttpRedactionType::Header, 'content-type' ) ],
                [
                    'Host'         => [ 'example.com' ],
                    'Content-Type' => [ 'redacted' ],
                ],
            ],
        ];
    }

    protected function query_no_values_provider(): iterable {
        return [
            'param_with_no_value'       => [
                'https://example.com/api/test?flag&param=value',
                [ new RedactionItem( HttpRedactionType::Query, 'flag' ) ],
                'flag=redacted&param=value',
            ],
            'multiple_params_no_values' => [
                'https://example.com/api/test?flag1&flag2&param=value',
                [ new RedactionItem( HttpRedactionType::Query, 'flag1' ) ],
                'flag1=redacted&flag2=&param=value',
            ],
        ];
    }

    protected function query_special_chars_provider(): iterable {
        return [
            'url_encoded_characters' => [
                'https://example.com/api/test?search=hello%20world&special=%3D%26%3F',
                [ new RedactionItem( HttpRedactionType::Query, 'special' ) ],
                'search=hello+world&special=redacted',
            ],
            'unicode_characters'     => [
                'https://example.com/api/test?name=José&city=São Paulo',
                [ new RedactionItem( HttpRedactionType::Query, 'name' ) ],
                'name=redacted&city=S%C3%A3o+Paulo',
            ],
        ];
    }

    protected function html_xml_content_provider(): iterable {
        return [
            'html_content_not_redacted'     => [
                'text/html',
                '<html><body><div class="password">secret</div></body></html>',
                [ new RedactionItem( HttpRedactionType::Request, 'password' ) ],
                '<html><body><div class="password">secret</div></body></html>',
            ],
            'xml_content_not_redacted'      => [
                'application/xml',
                '<?xml version="1.0"?><root><password>secret</password></root>',
                [ new RedactionItem( HttpRedactionType::Request, 'password' ) ],
                '<?xml version="1.0"?><root><password>secret</password></root>',
            ],
            'text_xml_content_not_redacted' => [
                'text/xml',
                '<?xml version="1.0"?><data><secret>hidden</secret></data>',
                [ new RedactionItem( HttpRedactionType::Request, 'secret' ) ],
                '<?xml version="1.0"?><data><secret>hidden</secret></data>',
            ],
        ];
    }

    protected function url_edge_cases_provider(): iterable {
        return [
            'empty_query_string'     => [
                'https://example.com/api/test?',
                [ new RedactionItem( HttpRedactionType::Query, 'anything' ) ],
                '',
            ],
            'no_query_string'        => [
                'https://example.com/api/test',
                [ new RedactionItem( HttpRedactionType::Query, 'anything' ) ],
                '',
            ],
            'malformed_query_params' => [
                'https://example.com/api/test?=value&key=&another=test',
                [ new RedactionItem( HttpRedactionType::Query, 'another' ) ],
                'key=&another=redacted',
            ],
        ];
    }

    protected function response_header_redaction_provider(): iterable {
        return [
            'redact_response_header' => [
                [
                    'Set-Cookie'   => 'session=abc123',
                    'Content-Type' => 'application/json',
                ],
                [ new RedactionItem( HttpRedactionType::Header, 'Set-Cookie' ) ],
                [
                    'Set-Cookie'   => [ 'redacted' ],
                    'Content-Type' => [ 'application/json' ],
                ],
            ],
            'remove_response_header' => [
                [
                    'Set-Cookie'   => 'session=abc123',
                    'Content-Type' => 'application/json',
                ],
                [ new RedactionItem( HttpRedactionType::Header, 'Set-Cookie', true ) ],
                [ 'Content-Type' => [ 'application/json' ] ],
            ],
        ];
    }

    protected function mixed_redaction_types_provider(): iterable {
        return [
            'always_with_specific_types' => [
                'https://example.com/api/test?token=secret&public=visible',
                [
                    'Authorization' => 'Bearer xyz',
                    'Content-Type'  => 'application/json',
                ],
                '{"password":"hidden","data":"public"}',
                [
                    new RedactionItem( HttpRedactionType::Always, 'token' ),
                    new RedactionItem( HttpRedactionType::Request, 'password' ),
                ],
                [
                    'query'   => 'token=redacted&public=visible',
                    'headers' => [
                        'Host'          => [ 'example.com' ],
                        'Authorization' => [ 'Bearer xyz' ],
                        'Content-Type'  => [ 'application/json' ],
                    ],
                    'body'    => '{"password":"redacted","data":"public"}',
                ],
            ],
        ];
    }

    protected function additional_redactions_provider(): iterable {
        return [
            'config_and_additional_combined'     => [
                'https://example.com/api/test?config_param=value1&additional_param=value2',
                [
                    'Config-Header'     => 'config-value',
                    'Additional-Header' => 'additional-value',
                    'Content-Type'      => 'application/json',
                ],
                '{"config_field":"config-data","additional_field":"additional-data"}',
                [ new RedactionItem( HttpRedactionType::Query, 'config_param' ) ],
                [ new RedactionItem( HttpRedactionType::Query, 'additional_param' ) ],
                [
                    'query'   => 'config_param=redacted&additional_param=redacted',
                    'headers' => [
                        'Host'              => [ 'example.com' ],
                        'Config-Header'     => [ 'config-value' ],
                        'Additional-Header' => [ 'additional-value' ],
                        'Content-Type'      => [ 'application/json' ],
                    ],
                    'body'    => '{"config_field":"config-data","additional_field":"additional-data"}',
                ],
            ],
            'additional_redacts_different_types' => [
                'https://example.com/api/test?param=value',
                [
                    'Authorization' => 'Bearer token',
                    'Content-Type'  => 'application/json',
                ],
                '{"password":"secret","data":"public"}',
                [],
                [
                    new RedactionItem( HttpRedactionType::Query, 'param' ),
                    new RedactionItem( HttpRedactionType::Header, 'Authorization' ),
                    new RedactionItem( HttpRedactionType::Request, 'password' ),
                ],
                [
                    'query'   => 'param=redacted',
                    'headers' => [
                        'Host'          => [ 'example.com' ],
                        'Authorization' => [ 'redacted' ],
                        'Content-Type'  => [ 'application/json' ],
                    ],
                    'body'    => '{"password":"redacted","data":"public"}',
                ],
            ],
        ];
    }

    protected function redaction_precedence_provider(): iterable {
        return [
            'additional_redactions_added_after_config' => [
                'https://example.com/api/test?param=value',
                [
                    'Authorization' => 'Bearer token',
                    'Content-Type'  => 'application/json',
                ],
                '{"field":"data"}',
                [
                    new RedactionItem( HttpRedactionType::Query, 'param' ),
                    new RedactionItem( HttpRedactionType::Header, 'Authorization' ),
                ],
                [
                    new RedactionItem( HttpRedactionType::Request, 'field' ),
                ],
                [
                    'query'   => 'param=redacted',
                    'headers' => [
                        'Host'          => [ 'example.com' ],
                        'Authorization' => [ 'redacted' ],
                        'Content-Type'  => [ 'application/json' ],
                    ],
                    'body'    => '{"field":"redacted"}',
                ],
            ],
            'overlapping_redaction_types_both_applied' => [
                'https://example.com/api/test?param=value',
                [
                    'Content-Type' => 'application/json',
                ],
                '{"field":"data"}',
                [ new RedactionItem( HttpRedactionType::Query, 'param' ) ],
                [ new RedactionItem( HttpRedactionType::Query, 'param' ) ],
                [
                    'query'   => 'param=redacted',
                    'headers' => [
                        'Host'         => [ 'example.com' ],
                        'Content-Type' => [ 'application/json' ],
                    ],
                    'body'    => '{"field":"data"}',
                ],
            ],
        ];
    }

    protected function additional_redaction_types_provider(): iterable {
        return [
            'query_redaction_only'           => [
                'https://example.com/api/test?secret=hidden&public=visible',
                [ 'Content-Type' => 'application/json' ],
                '{"data":"content"}',
                [ new RedactionItem( HttpRedactionType::Query, 'secret' ) ],
                [
                    'query'   => 'secret=redacted&public=visible',
                    'headers' => [
                        'Host'         => [ 'example.com' ],
                        'Content-Type' => [ 'application/json' ],
                    ],
                    'body'    => '{"data":"content"}',
                ],
            ],
            'header_redaction_only'          => [
                'https://example.com/api/test?param=value',
                [
                    'Authorization' => 'Bearer token',
                    'Content-Type'  => 'application/json',
                ],
                '{"data":"content"}',
                [ new RedactionItem( HttpRedactionType::Header, 'Authorization' ) ],
                [
                    'query'   => 'param=value',
                    'headers' => [
                        'Host'          => [ 'example.com' ],
                        'Authorization' => [ 'redacted' ],
                        'Content-Type'  => [ 'application/json' ],
                    ],
                    'body'    => '{"data":"content"}',
                ],
            ],
            'request_body_redaction_only'    => [
                'https://example.com/api/test?param=value',
                [ 'Content-Type' => 'application/json' ],
                '{"secret":"hidden","public":"visible"}',
                [ new RedactionItem( HttpRedactionType::Request, 'secret' ) ],
                [
                    'query'   => 'param=value',
                    'headers' => [
                        'Host'         => [ 'example.com' ],
                        'Content-Type' => [ 'application/json' ],
                    ],
                    'body'    => '{"secret":"redacted","public":"visible"}',
                ],
            ],
            'always_redaction_type'          => [
                'https://example.com/api/test?token=secret',
                [
                    'Authorization' => 'Bearer token',
                    'Content-Type'  => 'application/json',
                ],
                '{"token":"secret","data":"public"}',
                [ new RedactionItem( HttpRedactionType::Always, 'token' ) ],
                [
                    'query'   => 'token=redacted',
                    'headers' => [
                        'Host'          => [ 'example.com' ],
                        'Authorization' => [ 'Bearer token' ],
                        'Content-Type'  => [ 'application/json' ],
                    ],
                    'body'    => '{"token":"redacted","data":"public"}',
                ],
            ],
            'multiple_additional_redactions' => [
                'https://example.com/api/test?secret1=hidden&secret2=hidden&public=visible',
                [
                    'Authorization' => 'Bearer token',
                    'Secret-Header' => 'secret-value',
                    'Content-Type'  => 'application/json',
                ],
                '{"password":"hidden","token":"secret","data":"public"}',
                [
                    new RedactionItem( HttpRedactionType::Query, 'secret1' ),
                    new RedactionItem( HttpRedactionType::Query, 'secret2' ),
                    new RedactionItem( HttpRedactionType::Header, 'Secret-Header' ),
                    new RedactionItem( HttpRedactionType::Request, 'password' ),
                    new RedactionItem( HttpRedactionType::Request, 'token' ),
                ],
                [
                    'query'   => 'secret1=redacted&secret2=redacted&public=visible',
                    'headers' => [
                        'Host'          => [ 'example.com' ],
                        'Authorization' => [ 'Bearer token' ],
                        'Secret-Header' => [ 'redacted' ],
                        'Content-Type'  => [ 'application/json' ],
                    ],
                    'body'    => '{"password":"redacted","token":"redacted","data":"public"}',
                ],
            ],
        ];
    }

    private function mock_http_redactions_enabled() {
        $this->config->expects( $this->once() )
            ->method( 'is_http_redactions_enabled' )
            ->willReturn( true );
    }
}
