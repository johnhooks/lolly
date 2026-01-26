import {
    StoreDescriptor,
    ReduxStoreConfig,
} from '@wordpress/data/build-types/redux-store';

import type { Thunk } from '../../thunk';
import type { Settings, WpRestApiError } from '../../types';

import * as actions from './actions';
import * as selectors from './selectors';

/**
 * Drop-in status returned by the API.
 */
export interface DropinStatus {
    installed: boolean;
    is_lolly: boolean;
    version: string | null;
    writable: boolean;
}

export type Action =
    | { type: 'EDIT_SETTINGS_RECORD'; edits: Partial<Settings> }
    | { type: 'SAVE_SETTINGS_RECORD_START' }
    | { type: 'SAVE_SETTINGS_RECORD_FINISHED'; settings: Settings }
    | { type: 'SAVE_SETTINGS_RECORD_FAILED'; error: WpRestApiError }
    | { type: 'FETCH_SETTINGS_START' }
    | { type: 'FETCH_SETTINGS_FINISHED'; settings: Settings }
    | { type: 'FETCH_SETTINGS_FAILED'; error: WpRestApiError }
    | { type: 'FETCH_DROPIN_STATUS_START' }
    | { type: 'FETCH_DROPIN_STATUS_FINISHED'; status: DropinStatus }
    | { type: 'FETCH_DROPIN_STATUS_FAILED'; error: WpRestApiError }
    | { type: 'INSTALL_DROPIN_START' }
    | { type: 'INSTALL_DROPIN_FINISHED'; status: DropinStatus }
    | { type: 'INSTALL_DROPIN_FAILED'; error: WpRestApiError }
    | { type: 'UNINSTALL_DROPIN_START' }
    | { type: 'UNINSTALL_DROPIN_FINISHED'; status: DropinStatus }
    | { type: 'UNINSTALL_DROPIN_FAILED'; error: WpRestApiError }
    | { type: 'CLEAR_DROPIN_ERROR' };

export interface State {
    settings: SettingsState;
    edits: EditsState;
    dropin: DropinState;
}

export interface SettingsState {
    settings: Settings | undefined;
    isLoading: boolean;
    error: WpRestApiError | undefined;
}

export interface EditsState {
    // @todo Since the value is edited through Monaco, perhaps this should be a
    // string. I don't think we should JSON decode on every change to the Monaco
    // value.
    edits: Partial<Settings>;
    isSaving: boolean;
    error: WpRestApiError | undefined;
}

export interface DropinState {
    status: DropinStatus | undefined;
    isLoading: boolean;
    isInstalling: boolean;
    isUninstalling: boolean;
    error: WpRestApiError | undefined;
}

export type SettingsThunk = Thunk<
    Action,
    StoreDescriptor<ReduxStoreConfig<State, typeof actions, typeof selectors>>
>;
