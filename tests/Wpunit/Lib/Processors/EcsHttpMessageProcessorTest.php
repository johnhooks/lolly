<?php

declare(strict_types=1);

namespace Tests\Wpunit\Lib\Processors;

use Codeception\Attribute\DataProvider;
use Dozuki\Lib\Processors\EcsHttpMessageProcessor;
use Dozuki\GuzzleHttp\Psr7\Request;
use Dozuki\GuzzleHttp\Psr7\Response;
use Dozuki\Monolog\Processor\ProcessorInterface;
use lucatume\WPBrowser\TestCase\WPTestCase;
use Tests\Support\Concerns\BuildsLogRecords;

class EcsHttpMessageProcessorTest extends WpTestCase {
    use BuildsLogRecords;

    private ProcessorInterface $processor;

    public function setUp(): void {
        $this->processor = new EcsHttpMessageProcessor();
    }

    #[DataProvider( 'http_request_provider' )]
    public function testItShouldProcessHttpRequest( Request $request, array $expected ): void {
        $record = $this->build_log_record( context: [ 'request' => $request ] );
        $result = call_user_func( $this->processor, $record );
        $this->assertEqualsCanonicalizing( $expected, $result->extra );
        $this->assertArrayNotHasKey( 'request', $result->context );
    }

    #[DataProvider( 'http_response_provider' )]
    public function testItShouldProcessHttpResponse( Response $request, array $expected ): void {
        $record = $this->build_log_record( context: [ 'response' => $request ] );
        $result = call_user_func( $this->processor, $record );
        $this->assertEqualsCanonicalizing( $expected, $result->extra );
        $this->assertArrayNotHasKey( 'response', $result->context );
    }

    protected function http_request_provider(): iterable {
        return [
            'get_request_with_accept_header'    => [
                new Request(
                    'GET',
                    'https://example.com/',
                    [ 'Accepts' => 'application/json; charset=utf-8' ],
                ),
                [
                    'http' => [
                        'version' => '1.1',
                        'request' => [
                            'method'  => 'get',
                            'headers' => [
                                'host'    => [ 'example.com' ],
                                'accepts' => [ 'application/json; charset=utf-8' ],
                            ],
                        ],
                    ],
                ],
            ],
            'post_request_with_json_body'       => [
                new Request(
                    'POST',
                    'https://example.com/',
                    [ 'Content-Type' => 'application/json; charset=utf-8' ],
                    json_encode(
                        [
                            'username'  => 'tester',
                            'firstname' => 'Mary',
                            'lastname'  => 'Tester',
                        ]
                    ),
                ),
                [
                    'http' => [
                        'version' => '1.1',
                        'request' => [
                            'method'  => 'post',
                            'headers' => [
                                'host'         => [ 'example.com' ],
                                'content-type' => [ 'application/json; charset=utf-8' ],
                            ],
                            'body'    => [
                                'bytes'   => 60,
                                'content' => '{"username":"tester","firstname":"Mary","lastname":"Tester"}',
                            ],
                        ],
                    ],
                ],
            ],
            'get_request_with_query_parameters' => [
                new Request(
                    'GET',
                    'https://example.com/test?pram1=abc&pram2=111&pram3%5B0%5D=x&pram3%5B1%5D=y&pram3%5B2%5D=z',
                    [ 'Accepts' => 'application/json; charset=utf-8' ],
                ),
                [
                    'http' => [
                        'version' => '1.1',
                        'request' => [
                            'method'  => 'get',
                            'headers' => [
                                'host'    => [ 'example.com' ],
                                'accepts' => [ 'application/json; charset=utf-8' ],
                            ],
                        ],
                    ],
                ],
            ],
            'post_request_with_form_data'       => [
                new Request(
                    'POST',
                    'https://example.com/',
                    [ 'Content-Type' => 'application/x-www-form-urlencoded' ],
                    http_build_query(
                        [
                            'username'  => 'tester',
                            'firstname' => 'Mary',
                            'lastname'  => 'Tester',
                        ]
                    ),
                ),
                [
                    'http' => [
                        'version' => '1.1',
                        'request' => [
                            'method'  => 'post',
                            'headers' => [
                                'host'         => [ 'example.com' ],
                                'content-type' => [ 'application/x-www-form-urlencoded' ],
                            ],
                            'body'    => [
                                'bytes'   => 46,
                                'content' => 'username=tester&firstname=Mary&lastname=Tester',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    protected function http_response_provider(): iterable {
        return [
            'json_response_200_ok'           => [
                new Response(
                    200,
                    [ 'Content-Type' => 'application/json; charset=utf-8' ],
                    json_encode(
                        [
                            'username'  => 'tester',
                            'firstname' => 'Mary',
                            'lastname'  => 'Tester',
                        ]
                    ),
                ),
                [
                    'http' => [
                        'version'  => '1.1',
                        'response' => [
                            'status_code' => 200,
                            'headers'     => [
                                'content-type' => [ 'application/json; charset=utf-8' ],
                            ],
                            'body'        => [
                                'bytes'   => 60,
                                'content' => '{"username":"tester","firstname":"Mary","lastname":"Tester"}',
                            ],
                        ],
                    ],
                ],
            ],
            'html_response_400_client_error' => [
                new Response(
                    400,
                    [ 'Content-Type' => 'text/html; charset=utf-8' ],
                    '<html><head><title>Test</title></head><body>Status Client Error</body></html>'
                ),
                [
                    'http' => [
                        'version'  => '1.1',
                        'response' => [
                            'status_code' => 400,
                            'headers'     => [
                                'content-type' => [ 'text/html; charset=utf-8' ],
                            ],
                            'body'        => [
                                'bytes'   => 77,
                                'content' => '<html><head><title>Test</title></head><body>Status Client Error</body></html>',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
