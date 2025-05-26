<?php

declare(strict_types=1);

namespace Lolly;

use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Lib class.
 *
 * Utility class with methods to handle URL retrieval and JSON file processing.
 *
 * @package Lolly
 */
class Lib {
    public static function get_full_request_url(): string {
        global $wp;
        $protocol    = isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host        = $_SERVER['HTTP_HOST'] ?? null;
        $request_uri = $_SERVER['REQUEST_URI'] ?? null;

        if ( $host && $request_uri ) {
            return $protocol . '://' . $host . $request_uri;
        }

        return home_url( add_query_arg( array(), $wp->request ) );
    }

    /**
     * Load and decoce a JSON file.
     *
     * @param string $path
     *
     * @return array|WP_Error
     */
    public static function load_json_file( string $path ) {
        if ( file_exists( $path ) ) {
            $raw = file_get_contents( $path );

            if ( $raw === false ) {
                return new WP_Error( 'lolly.load-file-failed', 'Lolly Log failed to read a JSON file.', [ 'path' => $path ] );
            }

            $decoded = json_decode( $raw, true );

            if ( json_last_error() !== JSON_ERROR_NONE ) {
                return new WP_Error( 'lolly.json-decode-failed', 'Lolly Log failed to decode a JSON file.', [ 'path' => $path ] );
            }

            return $decoded;
        }

        return new WP_Error( 'lolly.file-not-found', 'Lolly Log failed to find a JSON file.', [ 'path' => $path ] );
    }
}
