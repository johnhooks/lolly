<?php

namespace Dozuki\Config;

use Dozuki\GuzzleHttp\Psr7\Uri;
use Dozuki\Lib\Contracts\Redactors\Config as RedactorConfig;
use Dozuki\Lib\Contracts\Whitelist\Config as WhitelistConfig;
use Dozuki\Psr\Http\Message\UriInterface;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Each of the logging modules should be:
// - Configurable
// - Can disable
// - Whitelist or blacklist certain request.


// If host api.wordpress.org and path starts with /core/version-check, remove the
// query, it's huge and not very helpful.

// @todo Add a similar system for whitelisting.

// The logging redaction to merge into this system is
// ["query" => ['user']]
// could we support something like JSON path `["response" => ["users.*.password"]]`

/**
 * The plugin configuration.
 *
 * @phpstan-type HttpRedactionItem array{
 *     type: string,
 *     value: string,
 *     remove?: bool
 *  }
 *
 * @phpstan-type HttpPathRedactions array{
 *      path: string,
 *      redactions: array<HttpRedactionItem>,
 *      glob?: bool
 *  }
 *
 * @phpstan-type HttpHostRedactions array{
 *      host: string,
 *      paths: array<HttpPathRedactions>
 *  }
 *
 * @phpstan-type HttpWhitelistItem array{
 *     path: string,
 *     glob?: bool
 * }
 *
 * @phpstan-type HttpHostWhitelist array{
 *      host: string,
 *      paths: array<HttpWhitelistItem>,
 *      glob?: bool
 * }
 *
 * @phpstan-type HttpLoggingConfig array{
 *      verion: int,
 *      enabled: bool,
 *
 *
 *      redactions: array<HttpHostRedactions>,
 *      whitelist: array<HttpHostWhitelist>,
 *  }
 */
class Config implements RedactorConfig, WhitelistConfig {
    public const OPTION_SLUG = 'dozuki_settings';

    private readonly string $log_dir_path;

    /**
     * @var array<string>
     */
    private readonly array $http_body_blacklist;

    /**
     * @var array<string>
     */
    private readonly array $http_query_blacklist;

    /**
     * @var HttpLoggingConfig|WP_Error|null
     */
    private array|WP_Error|null $http_logging_config = null;


    /**
     * @param string $log_dir_path The directory find the log file.
     */
    public function __construct( string $log_dir_path, ) {
        $this->log_dir_path = $log_dir_path;

        // @todo This should be handled by an action
        $this->init();
    }

    public function init(): void {
        $this->http_logging_config = $this->load();
        if ( is_wp_error( $this->http_logging_config ) ) {
            dozuki()->error(
                '[Dozuki] Failed to load the configuration.',
                [
                    'wp_error' => $this->http_logging_config,
                ]
            );
        }
    }

    public function get_log_dir_path(): string {
        return $this->log_dir_path;
    }

    /**
     * @inheritDoc
     */
    public function is_whitelist_enabled(): bool {
        return $this->http_logging_config['whitelist_enabled'] ?? false;
    }

    /**
     * @inheritDoc
     */
    public function is_http_url_whitelisted( UriInterface|string $url ): bool {
        $url  = $url instanceof Uri ? $url : new Uri( $url );
        $host = $url->getHost();
        $path = $url->getPath();

        foreach ( $this->http_logging_config['whitelist'] as $current ) {
            $glob = $current['glob'] ?? false;

            if (
                ( $glob && str_ends_with( $host, $current['host'] ) ) ||
                ( $current['host'] === $host )
            ) {
                foreach ( $current['paths'] as $current_path ) {
                    $glob = $current_path['glob'] ?? false;

                    if (
                        ( $glob && str_starts_with( $path, $current_path['path'] ) ) ||
                        ( $current_path['path'] === $path )
                    ) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function get_http_redactions( UriInterface|string $url ): array {
        $url  = $url instanceof Uri ? $url : new Uri( $url );
        $host = $url->getHost();
        $path = $url->getPath();

        /** @var array<HttpHostRedactions> $redaction_sets */
        $redaction_sets = [];

        foreach ( $this->http_logging_config['redactions'] as $current ) {
            if ( $current['host'] === '*' || $current['host'] === $host ) {
                $redaction_sets[] = $current;
            }
        }

        /** @var array<HttpRedactionItem> $redactions */
        $redactions = [];

        foreach ( $redaction_sets as $current ) {
            foreach ( $current['paths'] as $current_path ) {
                $glob = $current_path['glob'] ?? false;

                if (
                    $current_path['path'] === '*' ||
                    ( $glob && str_starts_with( $path, $current_path['path'] ) ) ||
                    ( $current_path['path'] === $path )
                ) {
                    $redactions[] = $current_path['redactions'];
                }
            }
        }

        return $redactions;
    }

    /**
     * Save the HTTP logging config to the database.
     *
     * @return null|WP_Error
     */
    public function save() {
        $config = $this->validate_config( $this->http_logging_config );

        if ( is_wp_error( $config ) ) {
            return $config;
        }

        $result = update_option( self::OPTION_SLUG, $config );

        if ( $result === false ) {
            return new WP_Error( 'dozuki.config', __( 'Dozuki Logger failed to save the configuration to the database.', 'dozuki' ) );
        }

        return null;
    }

    /**
     * Validate input against the HTTP logging config schema.
     *
     * @param array<string,mixed> $input
     *
     * @return HttpLoggingConfig|WP_Error
     */
    public function validate_config( array $input ): array|WP_Error {
        require_once ABSPATH . 'wp-includes/rest-api.php';

        $schema = $this->load_config_schema();

        if ( is_wp_error( $schema ) ) {
            return $schema;
        }

        $result = rest_validate_value_from_schema( $input, $schema );

        if ( is_wp_error( $result ) ) {
            $result->add( 'dozuki.config', __( 'Dozuki Logger configuration is invalid.', 'dozuki' ) );

            return $result;
        }

        return $input;
    }

    /**
     * Load the HTTP logging config schema.
     *
     * @return HttpLoggingConfig|WP_Error
     */
    public function load_config_schema(): array|WP_Error {
        $schema_path = trailingslashit( DOZUKI_PLUGIN_DIR ) . 'resources/schemas/http-logging-config.json';
        $schema      = \Dozuki\Lib::load_json_file( $schema_path );

        if ( is_wp_error( $schema ) ) {
            $schema->add( 'dozuki.config', __( 'Dozuki Logger failed to load the HTTP logging config schema.', 'dozuki' ) );

            return $schema;
        }

        return $schema;
    }

    /**
     * Get the initialized Http logging config.
     *
     * @return HttpLoggingConfig|WP_Error
     */
    private function get_config(): array|WP_Error {
        if ( $this->http_logging_config === null ) {
            return new WP_Error( 'dozuki.config.not_initialized', __( 'Dozuki Logger failed to load the configuration.', 'dozuki' ) );
        }

        return $this->http_logging_config;
    }

    public function register_settings(): void {
        $schema = $this->load_config_schema();

        if ( is_wp_error( $schema ) ) {
            dozuki()->error(
                '[Dozuki] Failed to register settings.',
                [
                    'wp_error' => $schema,
                ]
            );

            return;
        }

        register_setting(
            self::OPTION_SLUG,
            self::OPTION_SLUG,
            [
                'type'         => 'object',
                'description'  => esc_html__( 'The Dozuki logging configuration.', 'dozuki' ),
                // We don't need this right now, but I need to remember it is here.
                // It will provide the dozuki_settings value from the request.
                // 'sanitize_callback' => [$this, 'sanitize_setting'],
                'default'      => [
                    'version'                        => 1,
                    'enabled'                        => false,
                    'wp_rest_logging_enabled'        => true,
                    'wp_http_client_logging_enabled' => true,
                    'http_redactions_enabled'        => true,
                    'http_whitelist_enabled'         => false,
                    'http_redactions'                => [],
                    'http_whitelist'                 => [],
                ],
                'show_in_rest' => [
                    'schema' => $schema,
                ],
            ]
        );
    }

    /**
     * @return HttpLoggingConfig|WP_Error
     */
    private function load(): array|WP_Error {
        $config = $this->load_saved_config();

        if ( is_wp_error( $config ) ) {
            return $config;
        } elseif ( $config === null ) {
            $config = $this->load_default_config();
        }

        return $config;
    }

    /**
     * @return HttpLoggingConfig|WP_Error
     */
    private function load_default_config(): array|WP_Error {
        $path   = trailingslashit( DOZUKI_PLUGIN_DIR ) . 'config/default-logging-config.json';
        $config = \Dozuki\Lib::load_json_file( $path );

        if ( $config instanceof WP_Error ) {
            $config->add( 'dozuki.config', __( 'Dozuki Logger failed to load the default configuration.', 'dozuki' ) );

        }

        unset( $config['$schema'] );

        return $config;
    }

    /**
     * @return HttpLoggingConfig|WP_Error
     */
    private function load_saved_config(): array|null|WP_Error {
        if ( function_exists( 'get_option' ) ) {
            /** @var HttpLoggingConfig|false $config */
            $config = get_option( self::OPTION_SLUG, [] );

            if ( $config === false || ! is_array( $config ) || count( $config ) === 0 ) {
                return null;
            }

            return $config;
        }

        return new WP_Error( 'dozuki.config', __( 'Dozuki Logger failed to load the configuration from the database.', 'dozuki' ) );
    }
}
