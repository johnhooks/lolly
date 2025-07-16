import {
    StoreDescriptor,
    ReduxStoreConfig,
} from '@wordpress/data/build-types/redux-store';

import { Settings } from '../../store/types';
import type { Thunk } from '../../thunk';
import { WpRestApiError } from '../../types';

import * as actions from './actions';
import * as selectors from './selectors';

export type Action =
    | { type: 'EDIT_SETTINGS_RECORD'; edits: Partial<Settings> }
    | { type: 'SAVE_SETTINGS_RECORD_START' }
    | { type: 'SAVE_SETTINGS_RECORD_FINISHED'; settings: Settings }
    | { type: 'SAVE_SETTINGS_RECORD_FAILED'; error: WpRestApiError }
    | { type: 'FETCH_SETTINGS_START' }
    | { type: 'FETCH_SETTINGS_FINISHED'; settings: Settings }
    | { type: 'FETCH_SETTINGS_FAILED'; error: WpRestApiError };

export interface State {
    settings: SettingsState;
    edits: EditsState;
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

export type SettingsThunk = Thunk<
    Action,
    StoreDescriptor<ReduxStoreConfig<State, typeof actions, typeof selectors>>
>;
