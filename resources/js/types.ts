/**
 * Simple feature config with just an enabled flag.
 */
export interface FeatureConfig {
    enabled: boolean;
}

/**
 * Authentication logging settings.
 */
export interface AuthLoggingConfig {
    enabled: boolean;
    login: boolean;
    logout: boolean;
    login_failed: boolean;
    password_changed: boolean;
    app_password_created: boolean;
    app_password_deleted: boolean;
}

/**
 * Settings returned by /lolly/v1/settings endpoint.
 *
 * Each feature is a nested object with an `enabled` flag, allowing
 * additional feature-specific settings to be added later.
 *
 * Note: http_redactions.rules and http_whitelist.rules arrays are stored
 * separately and will have their own endpoints in the future.
 */
export interface Settings {
    version: number;
    enabled: boolean;
    wp_rest_logging: FeatureConfig;
    wp_http_client_logging: FeatureConfig;
    wp_user_event_logging: FeatureConfig;
    wp_auth_logging: AuthLoggingConfig;
    http_redactions: FeatureConfig;
    http_whitelist: FeatureConfig;
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
