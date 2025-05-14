<?php

declare(strict_types=1);

namespace Tests\Wpunit\Lib\Processors;

use Codeception\Attribute\DataProvider;
use DateTimeImmutable;
use Dozuki\Lib\Processors\PsrLogMessageProcessor;
use Dozuki\Monolog\Level;
use Dozuki\Monolog\LogRecord;
use Dozuki\Monolog\Processor\ProcessorInterface;
use Exception;
use lucatume\WPBrowser\TestCase\WPTestCase;
use Tests\Support\Concerns\BuildsLogRecords;
use Tests\Support\WpunitTester;

class PsrLogMessageProcessorTest extends WPTestCase {
    use BuildsLogRecords;

    private ProcessorInterface $processor;

    public function setUp(): void {
        $this->processor = new PsrLogMessageProcessor();
    }

    public function testItDoesntInterpolateMissingKey() {
        $record = $this->build_log_record( message: '{key}' );
        $result = call_user_func( $this->processor, $record );

        $this->assertEquals( $result->message, '{key}' );
    }

    public function testItInterpolatesUndefinedForMissingPath() {
        $record = $this->build_log_record( message: '{key.path}' );
        $result = call_user_func( $this->processor, $record );

        $this->assertEquals( $result->message, '[undefined]' );
    }

    public function testItInterpolatesUndefinedForNonArrayPath() {
        $record = $this->build_log_record( message: '{key.path}', context: [ 'key' => 'nope' ] );
        $result = call_user_func( $this->processor, $record );

        $this->assertEquals( $result->message, '[undefined]' );
    }

    public function testItInterpolatesUndefinedForMissingNestedPath() {
        $record = $this->build_log_record( message: '{key.nested.path}' );
        $result = call_user_func( $this->processor, $record, [ 'key' => [ 'nested' => [] ] ] );

        $this->assertEquals( $result->message, '[undefined]' );
    }

    public function testItInterpolatesEnforcesMaxDepth() {
        $record = $this->build_log_record(
            message: '{key.path.too.deep.to.get}',
            context: [ 'key' => [ 'path' => [ 'too' => [ 'deep' => [ 'to' => [ 'get' => 'nope' ] ] ] ] ] ],
        );
        $result = call_user_func( $this->processor, $record );

        $this->assertEquals( $result->message, '[max depth exceeded]' );
    }

    public function testItInterpolatesConfiguresMaxDepth() {
        $processor = new PsrLogMessageProcessor( 2 );
        $record    = $this->build_log_record(
            message: '{key.path.too.deep.to.get}',
            context: [ 'key' => [ 'path' => [ 'too' => [ 'deep' => 'nope' ] ] ] ],
        );
        $result    = call_user_func( $this->processor, $record );

        $this->assertEquals( $result->message, '[max depth exceeded]' );
    }

    #[DataProvider( 'interpolateValueProvider' )]
    public function testItInterpolatesValue( $expected, $value ) {
        $record = $this->build_log_record( message: '{key}', context: [ 'key' => $value ] );
        $result = call_user_func( $this->processor, $record );

        $this->assertEquals( $result->message, $expected );
    }

    #[DataProvider( 'interpolateValueProvider' )]
    public function testItInterpolatesValueInsideMessage( $expected, $value ) {
        $record = $this->build_log_record( message: 'Surrounding {key} message', context: [ 'key' => $value ] );
        $result = call_user_func( $this->processor, $record );

        $this->assertEquals( $result->message, "Surrounding $expected message" );
    }

    #[DataProvider( 'interpolateValueProvider' )]
    public function testItInterpolatesMultipleValuesInsideMessage( $expected, $value ) {
        $record = $this->build_log_record(
            message: 'Surrounding {first} message {second}',
            context: [
                'first'  => $value,
                'second' => 'test',
            ]
        );
        $result = call_user_func( $this->processor, $record );

        $this->assertEquals( $result->message, "Surrounding $expected message test" );
    }

    #[DataProvider( 'interpolateValueProvider' )]
    public function testItInterpolatesPath( $expected, $value ) {
        $record = $this->build_log_record( message: '{test.prop}', context: [ 'test' => [ 'prop' => $value ] ] );
        $result = call_user_func( $this->processor, $record );

        $this->assertEquals( $result->message, $expected );
    }

    #[DataProvider( 'interpolateValueProvider' )]
    public function testItInterpolatesPathInsideMessage( $expected, $value ) {
        $record = $this->build_log_record(
            message: 'Surrounding {test.prop} message',
            context: [ 'test' => [ 'prop' => $value ] ]
        );
        $result = call_user_func( $this->processor, $record );

        $this->assertEquals( $result->message, "Surrounding $expected message" );
    }

    #[DataProvider( 'interpolateValueProvider' )]
    public function testItInterpolatesPathAndMultipleValuesInsideMessage( $expected, $value ) {
        $record = $this->build_log_record(
            message: 'Surrounding {test.prop} message {second}',
            context: [
                'test'   => [ 'prop' => $value ],
                'second' => 'test',
            ],
        );
        $result = call_user_func( $this->processor, $record );

        $this->assertSame( $result->message, "Surrounding $expected message test" );
    }

    #[DataProvider( 'interpolateComplexPathProvider' )]
    public function testItInterpolatesComplexPath( $path, $expected, $value ) {
        $record = $this->build_log_record( message: '{' . $path . '}', context: [ 'test' => $value ] );
        $result = call_user_func( $this->processor, $record );

        $this->assertSame( $result->message, $expected );
    }

    protected function interpolateValueProvider(): array {
        return [
            [ '[null]', null ],
            [ '[empty string]', '' ],
            [ '[true]', true ],
            [ '[false]', false ],
            [ 'test', 'test' ],
            [ '100', 100 ],
            [ '3.14', 3.14 ],
            [ 'error message', new Exception( 'error message' ) ],
            [ '[1, 2, 3]', [ 1, 2, 3 ] ],
            [ '[x, y, z]', [ 'x', 'y', 'z' ] ],
            [ '[1, 3.14, mixed]', [ 1, 3.14, 'mixed' ] ],
            [
                '[a => 1, b => 2, c => 3]',
                [
                    'a' => 1,
                    'b' => 2,
                    'c' => 3,
                ],
            ],
            [
                '[a => x, b => y, c => z]',
                [
                    'a' => 'x',
                    'b' => 'y',
                    'c' => 'z',
                ],
            ],
            [
                '[a => 1, b => 3.14, c => mixed]',
                [
                    'a' => 1,
                    'b' => 3.14,
                    'c' => 'mixed',
                ],
            ],
            [
                'call __toString',
                new class() {
                    public function __toString() {
                        return 'call __toString';
                    }
                },
            ],
            [
                '[object Dozuki\Monolog\LogRecord]',
                new LogRecord(
                    datetime: new DateTimeImmutable( 'now' ),
                    channel: 'test',
                    level: Level::Info,
                    message: '',
                    context: [],
                    extra: [],
                ),
            ],
        ];
    }

    protected function interpolateComplexPathProvider(): array {
        return [
            [
                'test.prop',
                'test',
                new class() {
                    public string $prop = 'test';
                },
            ],
            [
                'test.prop',
                '222',
                new class() {
                    public int $prop = 222;
                },
            ],
            [
                'test.prop',
                'call __toString',
                new class() {
                    public mixed $prop;

                    public function __construct() {
                        $this->prop = new class() {
                            public function __toString(): string {
                                return 'call __toString';
                            }
                        };
                    }
                },
            ],
            [
                'test.prop.int',
                '333',
                new class() {
                    public mixed $prop;

                    public function __construct() {
                        $this->prop = new class() {
                            public int $int = 333;
                        };
                    }
                },
            ],
            [
                'test.prop.array',
                '[x, y, z]',
                new class() {
                    public mixed $prop;

                    public function __construct() {
                        $this->prop = new class() {
                            public $array = [ 'x', 'y', 'z' ];
                        };
                    }
                },
            ],
            [
                'test.prop.array.1',
                'y',
                new class() {
                    public mixed $prop;

                    public function __construct() {
                        $this->prop = new class() {
                            public $array = [ 'x', 'y', 'z' ];
                        };
                    }
                },
            ],
        ];
    }
}
