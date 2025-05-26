<?php

namespace Tests\Integration;

use lucatume\WPBrowser\TestCase\WPTestCase;

class SampleTest extends WPTestCase {
    // Tests
    public function test_factory(): void {
        $post = static::factory()->post->create_and_get();

        $this->assertInstanceOf( \WP_Post::class, $post );
    }

    public function test_plugin_active(): void {
        $this->assertTrue( is_plugin_active( 'lolly/lolly.php' ) );
    }
}
