<?php

declare(strict_types=1);

namespace Tests\Wpunit\Dropin;

use Lolly\Dropin\DropinManager;
use lucatume\WPBrowser\TestCase\WPTestCase;

/**
 * Tests for the DropinManager class.
 */
class DropinManagerTest extends WPTestCase {

    private DropinManager $manager;

    public function setUp(): void {
        parent::setUp();

        $this->manager = new DropinManager();

        // Clean up any existing drop-in before each test.
        $this->remove_dropin_if_exists();
    }

    public function tearDown(): void {
        parent::tearDown();

        // Clean up after each test.
        $this->remove_dropin_if_exists();
    }

    private function remove_dropin_if_exists(): void {
        $path = $this->manager->get_dropin_path();
        if ( file_exists( $path ) ) {
            // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
            unlink( $path );
        }
    }

    public function testGetStatusReturnsNotInstalledWhenDropinMissing(): void {
        $status = $this->manager->get_status();

        $this->assertIsArray( $status );
        $this->assertFalse( $status['installed'] );
        $this->assertFalse( $status['is_lolly'] );
        $this->assertNull( $status['version'] );
        $this->assertTrue( $status['writable'] );
    }

    public function testInstallCreatesDropinFile(): void {
        $result = $this->manager->install();

        $this->assertTrue( $result );
        $this->assertTrue( file_exists( $this->manager->get_dropin_path() ) );
    }

    public function testInstallReturnsStatusWithInstalled(): void {
        $this->manager->install();

        $status = $this->manager->get_status();

        $this->assertTrue( $status['installed'] );
        $this->assertTrue( $status['is_lolly'] );
        $this->assertNotNull( $status['version'] );
    }

    public function testIsOursReturnsTrueForLollyDropin(): void {
        $this->manager->install();

        $this->assertTrue( $this->manager->is_ours() );
    }

    public function testIsOursReturnsFalseForThirdPartyDropin(): void {
        // Create a fake third-party drop-in.
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
        file_put_contents(
            $this->manager->get_dropin_path(),
            "<?php\n/**\n * Plugin Name: Some Other Plugin\n */"
        );

        $this->assertFalse( $this->manager->is_ours() );
    }

    public function testUninstallRemovesDropin(): void {
        $this->manager->install();
        $this->assertTrue( file_exists( $this->manager->get_dropin_path() ) );

        $result = $this->manager->uninstall();

        $this->assertTrue( $result );
        $this->assertFalse( file_exists( $this->manager->get_dropin_path() ) );
    }

    public function testUninstallReturnsErrorForThirdPartyDropin(): void {
        // Create a fake third-party drop-in.
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
        file_put_contents(
            $this->manager->get_dropin_path(),
            "<?php\n/**\n * Plugin Name: Some Other Plugin\n */"
        );

        $result = $this->manager->uninstall();

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertEquals( 'lolly_dropin_exists_third_party', $result->get_error_code() );
    }

    public function testInstallReturnsErrorWhenThirdPartyExists(): void {
        // Create a fake third-party drop-in.
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
        file_put_contents(
            $this->manager->get_dropin_path(),
            "<?php\n/**\n * Plugin Name: Some Other Plugin\n */"
        );

        $result = $this->manager->install();

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertEquals( 'lolly_dropin_exists_third_party', $result->get_error_code() );
    }

    public function testUninstallSucceedsWhenNotInstalled(): void {
        $this->assertFalse( file_exists( $this->manager->get_dropin_path() ) );

        $result = $this->manager->uninstall();

        $this->assertTrue( $result );
    }

    public function testCanReinstallDropin(): void {
        // First install.
        $this->manager->install();
        $this->assertTrue( $this->manager->is_installed() );

        // Uninstall.
        $this->manager->uninstall();
        $this->assertFalse( $this->manager->is_installed() );

        // Reinstall.
        $result = $this->manager->install();
        $this->assertTrue( $result );
        $this->assertTrue( $this->manager->is_installed() );
    }

    public function testGetDropinVersionReturnsVersion(): void {
        $this->manager->install();

        $version = $this->manager->get_dropin_version();

        $this->assertNotNull( $version );
        $this->assertMatchesRegularExpression( '/^\d+\.\d+\.\d+$/', $version );
    }

    public function testGetDropinVersionReturnsNullWhenNotInstalled(): void {
        $version = $this->manager->get_dropin_version();

        $this->assertNull( $version );
    }
}
