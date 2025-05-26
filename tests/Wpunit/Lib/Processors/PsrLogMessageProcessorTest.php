<?php

declare(strict_types=1);

namespace Tests\Wpunit\Lib\Processors;

use Codeception\Attribute\DataProvider;
use DateTimeImmutable;
use Lolly\Lib\Processors\PsrLogMessageProcessor;
use Lolly\Monolog\Level;
use Lolly\Monolog\LogRecord;
use Lolly\Monolog\Processor\ProcessorInterface;
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

    #[DataProvider( 'interpolate_value_provider' )]
    public function testItInterpolatesValue( $expected, $value ) {
        $record = $this->build_log_record( message: '{key}', context: [ 'key' => $value ] );
        $result = call_user_func( $this->processor, $record );

        $this->assertEquals( $result->message, $expected );
    }

    #[DataProvider( 'interpolate_value_provider' )]
    public function testItInterpolatesValueInsideMessage( $expected, $value ) {
        $record = $this->build_log_record( message: 'Surrounding {key} message', context: [ 'key' => $value ] );
        $result = call_user_func( $this->processor, $record );

        $this->assertEquals( $result->message, "Surrounding $expected message" );
    }

    #[DataProvider( 'interpolate_value_provider' )]
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

    #[DataProvider( 'interpolate_value_provider' )]
    public function testItInterpolatesPath( $expected, $value ) {
        $record = $this->build_log_record( message: '{test.prop}', context: [ 'test' => [ 'prop' => $value ] ] );
        $result = call_user_func( $this->processor, $record );

        $this->assertEquals( $result->message, $expected );
    }

    #[DataProvider( 'interpolate_value_provider' )]
    public function testItInterpolatesPathInsideMessage( $expected, $value ) {
        $record = $this->build_log_record(
            message: 'Surrounding {test.prop} message',
            context: [ 'test' => [ 'prop' => $value ] ]
        );
        $result = call_user_func( $this->processor, $record );

        $this->assertEquals( $result->message, "Surrounding $expected message" );
    }

    #[DataProvider( 'interpolate_value_provider' )]
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

    #[DataProvider( 'interpolate_complex_path_provider' )]
    public function testItInterpolatesComplexPath( $path, $expected, $value ) {
        $record = $this->build_log_record( message: '{' . $path . '}', context: [ 'test' => $value ] );
        $result = call_user_func( $this->processor, $record );

        $this->assertSame( $result->message, $expected );
    }

    protected function interpolate_value_provider(): array {
        return [
            'null_value'               => [ '[null]', null ],
            'empty_string'             => [ '[empty string]', '' ],
            'boolean_true'             => [ '[true]', true ],
            'boolean_false'            => [ '[false]', false ],
            'string_value'             => [ 'test', 'test' ],
            'integer_value'            => [ '100', 100 ],
            'float_value'              => [ '3.14', 3.14 ],
            'exception_object'         => [ 'error message', new Exception( 'error message' ) ],
            'numeric_array'            => [ '[1, 2, 3]', [ 1, 2, 3 ] ],
            'string_array'             => [ '[x, y, z]', [ 'x', 'y', 'z' ] ],
            'mixed_array'              => [ '[1, 3.14, mixed]', [ 1, 3.14, 'mixed' ] ],
            'associative_array_int'    => [
                '[a => 1, b => 2, c => 3]',
                [
                    'a' => 1,
                    'b' => 2,
                    'c' => 3,
                ],
            ],
            'associative_array_string' => [
                '[a => x, b => y, c => z]',
                [
                    'a' => 'x',
                    'b' => 'y',
                    'c' => 'z',
                ],
            ],
            'associative_array_mixed'  => [
                '[a => 1, b => 3.14, c => mixed]',
                [
                    'a' => 1,
                    'b' => 3.14,
                    'c' => 'mixed',
                ],
            ],
            'object_with_tostring'     => [
                'call __toString',
                new class() {
                    public function __toString() {
                        return 'call __toString';
                    }
                },
            ],
            'log_record_object'        => [
                '[object Lolly\Monolog\LogRecord]',
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

    protected function interpolate_complex_path_provider(): array {
        return [
            'object_string_property'        => [
                'test.prop',
                'test',
                new class() {
                    public string $prop = 'test';
                },
            ],
            'object_integer_property'       => [
                'test.prop',
                '222',
                new class() {
                    public int $prop = 222;
                },
            ],
            'nested_object_with_tostring'   => [
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
            'deeply_nested_object_property' => [
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
            'nested_object_array_property'  => [
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
            'nested_object_array_index'     => [
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
