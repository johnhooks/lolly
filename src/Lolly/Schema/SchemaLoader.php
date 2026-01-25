<?php

declare(strict_types=1);

namespace Lolly\Schema;

use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Loads JSON Schema files with version-based caching.
 *
 * Schemas are cached in transients with keys that include the plugin version.
 * This ensures fresh cache on plugin updates while avoiding repeated file reads.
 */
class SchemaLoader {

    private const TRANSIENT_PREFIX = 'lolly_schema_';
    private const CACHE_EXPIRATION = DAY_IN_SECONDS;

    /**
     * Load a schema by name.
     *
     * @param string $name The schema name (e.g., 'settings', 'redactions').
     *
     * @return array<string, mixed>|WP_Error The schema array or WP_Error on failure.
     */
    public function get( string $name ): array|WP_Error {
        $cache_key = $this->get_cache_key( $name );
        $cached    = get_transient( $cache_key );

        if ( $cached !== false && is_array( $cached ) ) {
            return $cached;
        }

        $schema = $this->load_from_file( $name );

        if ( is_wp_error( $schema ) ) {
            return $schema;
        }

        set_transient( $cache_key, $schema, self::CACHE_EXPIRATION );

        return $schema;
    }

    /**
     * Clear cached schema.
     *
     * @param string $name The schema name to clear, or empty to clear all.
     */
    public function clear_cache( string $name = '' ): void {
        if ( $name !== '' ) {
            delete_transient( $this->get_cache_key( $name ) );
            return;
        }

        // Clear all known schemas.
        $schemas = [ 'settings', 'redactions', 'whitelist' ];
        foreach ( $schemas as $schema_name ) {
            delete_transient( $this->get_cache_key( $schema_name ) );
        }
    }

    /**
     * Get the cache key for a schema.
     *
     * @param string $name The schema name.
     *
     * @return string The cache key including plugin version.
     */
    private function get_cache_key( string $name ): string {
        $version = defined( 'LOLLY_VERSION' ) ? LOLLY_VERSION : '0.0.0';

        return self::TRANSIENT_PREFIX . $name . '_' . $version;
    }

    /**
     * Load a schema from its JSON file.
     *
     * @param string $name The schema name.
     *
     * @return array<string, mixed>|WP_Error The schema array or WP_Error on failure.
     */
    private function load_from_file( string $name ): array|WP_Error {
        // Validate schema name to prevent path traversal.
        if ( preg_match( '/[^a-z0-9_-]/i', $name ) === 1 ) {
            return new WP_Error(
                'lolly_schema_invalid_name',
                sprintf(
                    /* translators: %s: schema name */
                    __( 'Invalid schema name: %s', 'lolly' ),
                    $name
                )
            );
        }

        $plugin_dir  = defined( 'LOLLY_PLUGIN_DIR' ) ? LOLLY_PLUGIN_DIR : '';
        $schema_path = trailingslashit( $plugin_dir ) . 'resources/schemas/' . $name . '.json';

        if ( ! file_exists( $schema_path ) ) {
            return new WP_Error(
                'lolly_schema_not_found',
                sprintf(
                    /* translators: %s: schema name */
                    __( 'Schema file not found: %s', 'lolly' ),
                    $name
                )
            );
        }

        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading local file.
        $contents = file_get_contents( $schema_path );

        if ( $contents === false ) {
            return new WP_Error(
                'lolly_schema_read_error',
                sprintf(
                    /* translators: %s: schema name */
                    __( 'Failed to read schema file: %s', 'lolly' ),
                    $name
                )
            );
        }

        $schema = json_decode( $contents, true );

        if ( json_last_error() !== JSON_ERROR_NONE ) {
            return new WP_Error(
                'lolly_schema_parse_error',
                sprintf(
                    /* translators: %1$s: schema name, %2$s: error message */
                    __( 'Failed to parse schema file %1$s: %2$s', 'lolly' ),
                    $name,
                    json_last_error_msg()
                )
            );
        }

        if ( ! is_array( $schema ) ) {
            return new WP_Error(
                'lolly_schema_invalid',
                sprintf(
                    /* translators: %s: schema name */
                    __( 'Schema file %s does not contain a valid JSON object.', 'lolly' ),
                    $name
                )
            );
        }

        return $schema;
    }
}
