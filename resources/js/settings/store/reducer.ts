import { combineReducers } from '@wordpress/data';

import type { SettingsState, State, Action, EditsState } from './types';

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

export default combineReducers({
    settings,
    edits,
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
    };
}
