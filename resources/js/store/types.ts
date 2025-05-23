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

export interface Settings {
    enabled: boolean;
    http_redactions_enabled: boolean;
    http_whitelist_enabled: boolean;
    wp_rest_logging_enabled: boolean;
    wp_http_client_logging_enabled: boolean;
    http_redactions: HttpRedactionSet[];
    http_whitelist: string[];
}

export interface State {
    settings: Settings;
    isSaving: boolean;
    message: {
        type: 'success' | 'error' | 'info' | 'warning';
        content: string;
    } | null;
}

export const DEFAULT_SETTINGS: Settings = {
    enabled: false,
    http_redactions_enabled: true,
    http_whitelist_enabled: false,
    wp_rest_logging_enabled: true,
    wp_http_client_logging_enabled: true,
    http_redactions: [],
    http_whitelist: [],
};

export const DEFAULT_STATE: State = {
    settings: DEFAULT_SETTINGS,
    isSaving: false,
    message: null,
};
