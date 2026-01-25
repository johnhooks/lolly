<?php

declare(strict_types=1);

namespace Lolly\Rest;

use Lolly\Config\Config;
use Lolly\Schema\SchemaLoader;
use WP_Error;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * REST API controller for Lolly settings.
 *
 * Provides a custom endpoint for managing plugin settings.
 *
 * Endpoints:
 * - GET /lolly/v1/settings Get all settings
 * - PUT /lolly/v1/settings Update settings
 * - OPTIONS /lolly/v1/settings Get schema (WordPress default)
 */
class SettingsController extends WP_REST_Controller {

    private const SCHEMA_NAME = 'settings';

    public function __construct(
        private readonly Config $config,
        private readonly SchemaLoader $schema_loader,
    ) {
        $this->namespace = 'lolly/v1';
        $this->rest_base = 'settings';
    }

    /**
     * Register REST routes.
     */
    public function register_routes(): void {
        // phpcs:disable Universal.Arrays.MixedKeyedUnkeyedArray, Universal.Arrays.MixedArrayKeyTypes -- Standard WP REST pattern.
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base,
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'get_settings' ],
                    'permission_callback' => [ $this, 'permissions_check' ],
                ],
                [
                    'methods'             => WP_REST_Server::EDITABLE,
                    'callback'            => [ $this, 'update_settings' ],
                    'permission_callback' => [ $this, 'permissions_check' ],
                    'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
                ],
                'schema' => [ $this, 'get_public_item_schema' ],
            ]
        );
        // phpcs:enable
    }

    /**
     * Check if the current user has permission to manage settings.
     *
     * @return bool|WP_Error True if allowed, WP_Error otherwise.
     */
    public function permissions_check(): bool|WP_Error {
        if ( ! current_user_can( 'manage_options' ) ) {
            return new WP_Error(
                'rest_forbidden',
                __( 'Sorry, you are not allowed to manage settings.', 'lolly' ),
                [ 'status' => rest_authorization_required_code() ]
            );
        }

        return true;
    }

    /**
     * Get all settings.
     *
     * @param WP_REST_Request<array<string, mixed>> $request The request object.
     *
     * @return WP_REST_Response|WP_Error The settings response.
     */
    public function get_settings( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        $settings = $this->get_settings_with_defaults();

        if ( is_wp_error( $settings ) ) {
            return $settings;
        }

        return new WP_REST_Response( $settings );
    }

    /**
     * Update settings.
     *
     * @param WP_REST_Request<array<string, mixed>> $request The request object.
     *
     * @return WP_REST_Response|WP_Error The updated settings or error.
     */
    public function update_settings( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        $new_settings = $request->get_json_params();

        if ( ! is_array( $new_settings ) ) {
            return new WP_Error(
                'rest_invalid_param',
                __( 'Invalid settings data.', 'lolly' ),
                [ 'status' => 400 ]
            );
        }

        // Merge with existing settings to preserve redactions/whitelist.
        $current_settings = get_option( Config::OPTION_SLUG, [] );
        $merged_settings  = array_replace_recursive( $current_settings, $new_settings );

        // Validate the core settings against the schema.
        $schema = $this->schema_loader->get( self::SCHEMA_NAME );

        if ( is_wp_error( $schema ) ) {
            return $schema;
        }

        $valid = rest_validate_value_from_schema( $new_settings, $schema );

        if ( is_wp_error( $valid ) ) {
            return $valid;
        }

        // Save the merged settings.
        $result = update_option( Config::OPTION_SLUG, $merged_settings );

        if ( $result === false ) {
            // Check if the option exists and has the same value.
            $current = get_option( Config::OPTION_SLUG );
            if ( $current !== $merged_settings ) {
                return new WP_Error(
                    'rest_cannot_update',
                    __( 'Failed to update settings.', 'lolly' ),
                    [ 'status' => 500 ]
                );
            }
        }

        // Reload the config to pick up the new values.
        $this->config->reload();

        $settings = $this->get_settings_with_defaults();

        if ( is_wp_error( $settings ) ) {
            return $settings;
        }

        return new WP_REST_Response( $settings );
    }

    /**
     * Get the settings schema for REST responses.
     *
     * @return array<string, mixed> The schema array.
     */
    public function get_item_schema(): array {
        if ( $this->schema !== null ) {
            return $this->add_additional_fields_schema( $this->schema );
        }

        $schema = $this->schema_loader->get( self::SCHEMA_NAME );

        if ( is_wp_error( $schema ) ) {
            // Return a minimal schema on error.
            return [
                '$schema' => 'http://json-schema.org/draft-04/schema#',
                'title'   => 'lolly-settings',
                'type'    => 'object',
            ];
        }

        $this->schema = $schema;

        return $this->add_additional_fields_schema( $this->schema );
    }

    /**
     * Get current settings with defaults applied.
     *
     * @return array<string, mixed>|WP_Error Settings array or error.
     */
    private function get_settings_with_defaults(): array|WP_Error {
        $schema = $this->schema_loader->get( self::SCHEMA_NAME );

        if ( is_wp_error( $schema ) ) {
            return $schema;
        }

        $saved    = get_option( Config::OPTION_SLUG, [] );
        $defaults = $this->extract_defaults( $schema );

        // Only return settings that are in the schema (not redactions/whitelist rules).
        // Use recursive merge so nested objects get their defaults filled in.
        $settings = [];
        foreach ( array_keys( $schema['properties'] ?? [] ) as $key ) {
            $default_value = $defaults[ $key ] ?? null;
            $saved_value   = $saved[ $key ] ?? null;

            if ( is_array( $default_value ) && is_array( $saved_value ) ) {
                // Recursively merge nested objects.
                $settings[ $key ] = array_replace_recursive( $default_value, $saved_value );
            } else {
                $settings[ $key ] = $saved_value ?? $default_value;
            }
        }

        return $settings;
    }

    /**
     * Extract default values from a schema.
     *
     * @param array<string, mixed> $schema The schema.
     *
     * @return array<string, mixed> The defaults.
     */
    private function extract_defaults( array $schema ): array {
        $defaults = [];

        foreach ( $schema['properties'] ?? [] as $key => $property ) {
            if ( isset( $property['default'] ) ) {
                $defaults[ $key ] = $property['default'];
            } elseif ( isset( $property['properties'] ) ) {
                $defaults[ $key ] = $this->extract_defaults( $property );
            }
        }

        return $defaults;
    }
}
