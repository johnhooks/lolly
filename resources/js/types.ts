export interface RedactionItem {
    type: string;
    value: string;
    remove?: boolean;
}

export interface PathRedaction {
    path: string;
    redactions: RedactionItem[];
    glob?: boolean;
}

export interface HttpRedactionSet {
    host: string;
    paths: PathRedaction[];
}

export interface PathWhitelist {
    path: string;
    glob?: boolean;
}

export interface HttpWhitelistSet {
    host: string;
    paths: PathWhitelist[];
    glob?: boolean;
}

export interface Settings {
    enabled: boolean;
    http_redactions_enabled: boolean;
    http_whitelist_enabled: boolean;
    wp_rest_logging_enabled: boolean;
    wp_http_client_logging_enabled: boolean;
    http_redactions: HttpRedactionSet[];
    http_whitelist: HttpWhitelistSet[];
}

/**
 * WordPress REST API error response format.
 *
 * Represents the JSON structure returned when a WP_Error is converted to
 * a REST response. Oddly, this doesn't seem to exist in a `@wordpress`
 * package.
 */
export interface WpRestApiError {
    /**
     * Error code (e.g., 'rest_invalid_param', 'rest_forbidden')
     */
    code: string;

    /**
     * Human-readable error message
     */
    message: string;

    /**
     * Additional error data
     */
    data?: {
        /**
         * HTTP status code
         */
        status: number;

        /**
         * Parameter-specific validation errors
         */
        params?: Record<string, string>;

        /**
         * Additional error details
         */
        details?: Record<string, unknown>;

        /**
         * Any other error data
         */
        [key: string]: unknown;
    };

    /**
     * Additional error information (for multiple errors)
     */
    additional_errors?: Array<{
        code: string;
        message: string;
        data?: unknown;
    }>;
}
