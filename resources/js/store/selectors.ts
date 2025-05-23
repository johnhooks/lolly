import type { State, Settings, HttpRedactionSet, HttpWhitelistSet } from './types';

export const getSettings = (state: State): Settings => {
    return state.settings;
};

export const isSaving = (state: State): boolean => {
    return state.isSaving;
};

export const getMessage = (state: State): State['message'] => {
    return state.message;
};

export const isEnabled = (state: State): boolean => {
    return state.settings.enabled;
};

export const getHttpRedactions = (state: State): HttpRedactionSet[] => {
    return state.settings.http_redactions;
};

export const getHttpWhitelist = (state: State): HttpWhitelistSet[] => {
    return state.settings.http_whitelist;
};

export const isHttpRedactionsEnabled = (state: State): boolean => {
    return state.settings.http_redactions_enabled;
};

export const isHttpWhitelistEnabled = (state: State): boolean => {
    return state.settings.http_whitelist_enabled;
};

export const isWpRestLoggingEnabled = (state: State): boolean => {
    return state.settings.wp_rest_logging_enabled;
};

export const isWpHttpClientLoggingEnabled = (state: State): boolean => {
    return state.settings.wp_http_client_logging_enabled;
};

export default {
    getSettings,
    isSaving,
    getMessage,
    isEnabled,
    getHttpRedactions,
    getHttpWhitelist,
    isHttpRedactionsEnabled,
    isHttpWhitelistEnabled,
    isWpRestLoggingEnabled,
    isWpHttpClientLoggingEnabled,
};
