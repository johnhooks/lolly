import { combineReducers } from '@wordpress/data';

import type {
    SettingsState,
    State,
    Action,
    EditsState,
    DropinState,
} from './types';

function settings(state: SettingsState, action: Action): SettingsState {
    switch (action.type) {
        case 'FETCH_SETTINGS_START': {
            return {
                ...state,
                isLoading: true,
            };
        }
        case 'FETCH_SETTINGS_FINISHED': {
            return {
                ...state,
                isLoading: false,
                settings: action.settings,
            };
        }
        case 'FETCH_SETTINGS_FAILED': {
            return {
                ...state,
                isLoading: false,
                error: action.error,
            };
        }
        case 'SAVE_SETTINGS_RECORD_FINISHED': {
            return {
                ...state,
                settings: action.settings,
            };
        }
    }

    return state;
}

function edits(state: EditsState, action: Action): EditsState {
    switch (action.type) {
        case 'EDIT_SETTINGS_RECORD': {
            return {
                ...state,
                edits: {
                    ...state.edits,
                    ...action.edits,
                },
            };
        }
        case 'SAVE_SETTINGS_RECORD_START': {
            return {
                ...state,
                isSaving: true,
                error: undefined,
            };
        }
        case 'SAVE_SETTINGS_RECORD_FINISHED': {
            return {
                ...state,
                edits: {},
                isSaving: false,
            };
        }
        case 'SAVE_SETTINGS_RECORD_FAILED': {
            return {
                ...state,
                isSaving: false,
                error: action.error,
            };
        }
    }
    return state;
}

function dropin(state: DropinState, action: Action): DropinState {
    switch (action.type) {
        case 'FETCH_DROPIN_STATUS_START': {
            return {
                ...state,
                isLoading: true,
                error: undefined,
            };
        }
        case 'FETCH_DROPIN_STATUS_FINISHED': {
            return {
                ...state,
                isLoading: false,
                status: action.status,
            };
        }
        case 'FETCH_DROPIN_STATUS_FAILED': {
            return {
                ...state,
                isLoading: false,
                error: action.error,
            };
        }
        case 'INSTALL_DROPIN_START': {
            return {
                ...state,
                isInstalling: true,
                error: undefined,
            };
        }
        case 'INSTALL_DROPIN_FINISHED': {
            return {
                ...state,
                isInstalling: false,
                status: action.status,
            };
        }
        case 'INSTALL_DROPIN_FAILED': {
            return {
                ...state,
                isInstalling: false,
                error: action.error,
            };
        }
        case 'UNINSTALL_DROPIN_START': {
            return {
                ...state,
                isUninstalling: true,
                error: undefined,
            };
        }
        case 'UNINSTALL_DROPIN_FINISHED': {
            return {
                ...state,
                isUninstalling: false,
                status: action.status,
            };
        }
        case 'UNINSTALL_DROPIN_FAILED': {
            return {
                ...state,
                isUninstalling: false,
                error: action.error,
            };
        }
        case 'CLEAR_DROPIN_ERROR': {
            return {
                ...state,
                error: undefined,
            };
        }
    }

    return state;
}

export default combineReducers({
    settings,
    edits,
    dropin,
});

export function initializeDefaultState(): State {
    return {
        settings: {
            settings: undefined,
            isLoading: false,
            error: undefined,
        },
        edits: {
            edits: {},
            isSaving: false,
            error: undefined,
        },
        dropin: {
            status: undefined,
            isLoading: false,
            isInstalling: false,
            isUninstalling: false,
            error: undefined,
        },
    };
}
