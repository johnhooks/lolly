<?php

namespace Lolly\Config;

use Lolly\GuzzleHttp\Psr7\Uri;
use Lolly\Lib\Contracts\Redactors\Config as RedactorConfig;
use Lolly\Lib\Contracts\Whitelist\Config as WhitelistConfig;
use Lolly\Lib\Enums\HttpRedactionType;
use Lolly\Lib\ValueObjects\Http\RedactionItem;
use Lolly\Psr\Http\Message\UriInterface;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// The logging redaction to merge into this system is ["query" => ['user']].

/**
 * Config class.
 *
 * The Lolly plugin configuration.
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
 *      http_redactions_enabled: bool,
 *      http_whitelist_enabled: bool,
 *      wp_rest_logging_enabled: bool,
 *      wp_http_client_logging_enabled: bool,
 *      http_redactions: array<HttpHostRedactions>,
 *      http_whitelist: array<HttpHostWhitelist>,
 *  }
 *
 * @package Lolly
 */
class Config implements RedactorConfig, WhitelistConfig {
    public const OPTION_SLUG = 'lolly_settings';

    private readonly string $log_dir_path;

    /**
     * @var HttpLoggingConfig|WP_Error|null
     */
    private array|WP_Error|null $http_logging_config = null;


    /**
     * @param string $log_dir_path The directory to log to.
     */
    public function __construct( string $log_dir_path, ) {
        $this->log_dir_path = $log_dir_path;

        // @todo This should be handled by an action
        $this->init();
    }

    public function init(): void {
        $this->http_logging_config = $this->load();
        if ( is_wp_error( $this->http_logging_config ) ) {
            lolly()->error(
                '[Lolly] Failed to load the configuration.',
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
     * Whether the logging feature is enabled.
     */
    public function is_logging_enabled(): bool {
        // @phpstan-ignore-next-line
        if ( defined( 'LOLLY_LOG_DISABLED' ) && LOLLY_LOG_DISABLED === true ) {
            return false;
        }

        return $this->http_logging_config['enabled'] ?? false;
    }

    /**
     * Whether the REST API logging feature is enabled.
     */
    public function is_wp_rest_logging_enabled(): bool {
        if ( ! $this->is_logging_enabled() ) {
            return false;
        }

        return $this->http_logging_config['wp_rest_logging_enabled'] ?? false;
    }

    /**
     * Whether the HTTP client logging feature is enabled.
     */
    public function is_wp_http_client_logging_enabled(): bool {
        if ( ! $this->is_logging_enabled() ) {
            return false;
        }

        return $this->http_logging_config['wp_http_client_logging_enabled'] ?? false;
    }

    /**
     * @inheritDoc
     */
    public function is_whitelist_enabled(): bool {
        return $this->http_logging_config['http_whitelist_enabled'] ?? false;
    }

    /**
     * @inheritDoc
     */
    public function is_http_url_whitelisted( UriInterface|string $url ): bool {
        $url  = $url instanceof Uri ? $url : new Uri( $url );
        $host = $url->getHost();
        $path = $url->getPath();

        foreach ( $this->http_logging_config['http_whitelist'] as $current ) {
            $glob = $current['glob'] ?? false;

            if (
                ( $glob && str_ends_with( $host, $current['host'] ) ) ||
                ( $current['host'] === $host )
            ) {
                foreach ( $current['paths'] as $current_path ) {
                    $glob = $current_path['glob'] ?? false;

                    if ( $current_path['path'] === '*' ) {
                        return true;
                    }

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
    public function is_http_redactions_enabled(): bool {
        return $this->http_logging_config['http_redactions_enabled'] ?? false;
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

        foreach ( $this->http_logging_config['http_redactions'] as $current ) {
            if ( $current['host'] === '*' || $current['host'] === $host ) {
                $redaction_sets[] = $current;
            }
        }

        /** @var RedactionItem[] $redactions */
        $redactions = [];

        foreach ( $redaction_sets as $current ) {
            foreach ( $current['paths'] as $current_path ) {
                $glob = $current_path['glob'] ?? false;

                if (
                    $current_path['path'] === '*' ||
                    ( $glob && str_starts_with( $path, $current_path['path'] ) ) ||
                    ( $current_path['path'] === $path )
                ) {
                    foreach ( $current_path['redactions'] as $redaction ) {
                        $redaction_type = HttpRedactionType::tryFrom( $redaction['type'] );

                        if ( $redaction_type === null ) {
                            lolly()->error(
                                '[Lolly] Invalid HTTP redaction type.',
                                [
                                    'type' => $redaction['type'],
                                ]
                            );

                            continue;
                        }

                        $redactions[] = new RedactionItem(
                            $redaction_type,
                            $redaction['value'],
                            $redaction['remove'] ?? false,
                        );
                    }
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
            return new WP_Error( 'lolly.config', __( 'Lolly Log failed to save the configuration to the database.', 'lolly' ) );
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
            $result->add( 'lolly.config', __( 'Lolly Log configuration is invalid.', 'lolly' ) );

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
        /** @var string $plugin_dir */
        $plugin_dir  = LOLLY_PLUGIN_DIR;
        $schema_path = trailingslashit( $plugin_dir ) . 'resources/schemas/http-logging-config.json';
        $schema      = \Lolly\Lib::load_json_file( $schema_path );

        if ( is_wp_error( $schema ) ) {
            $schema->add( 'lolly.config', __( 'Lolly Log failed to load the HTTP logging config schema.', 'lolly' ) );

            return $schema;
        }

        return $schema;
    }

    public function register_settings(): void {
        $schema = $this->load_config_schema();

        if ( is_wp_error( $schema ) ) {
            lolly()->error(
                '[Lolly] Failed to register settings.',
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
                'description'  => esc_html__( 'The Lolly logging configuration.', 'lolly' ),
                // We don't need this right now, but I need to remember it is here.
                // It will provide the lolly_settings value from the request:
                // 'sanitize_callback' => [$this, 'sanitize_setting'].
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
        /** @var string $plugin_dir */
        $plugin_dir = LOLLY_PLUGIN_DIR;
        $path       = trailingslashit( $plugin_dir ) . 'config/default-logging-config.json';
        $config     = \Lolly\Lib::load_json_file( $path );

        if ( $config instanceof WP_Error ) {
            $config->add( 'lolly.config', __( 'Lolly Log failed to load the default configuration.', 'lolly' ) );

        }

        unset( $config['$schema'] );

        return $config;
    }

    /**
     * @return HttpLoggingConfig|WP_Error|null
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

        return new WP_Error( 'lolly.config', __( 'Lolly Log failed to load the configuration from the database.', 'lolly' ) );
    }
}
