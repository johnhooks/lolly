import type { Settings, WpRestApiError } from '../../types';

import { State } from './types';

export function getSettings(state: State): Settings | undefined {
    return state.settings.settings;
}

export function isLoading(state: State): boolean {
    return state.settings.isLoading;
}

export function isSaving(state: State): boolean {
    return state.edits.isSaving;
}

export function getEdits(state: State): Partial<Settings> {
    return state.edits.edits;
}

export function getEditsForProperty<K extends keyof Settings>(
    state: State,
    key: K
): Settings[K] | undefined {
    return state.edits.edits[key];
}

/**
 * Whether the settings object has been edited.
 */
export function hasAnyEdits(state: State): boolean {
    return Object.keys(state.edits.edits).length > 0;
}

/**
 * Whether a specific property of the settings has been edited.
 */
export function hasEdits(state: State, key: keyof Settings): boolean {
    return key in state.edits.edits;
}

export function getEditError(state: State): WpRestApiError | undefined {
    return state.edits.error;
}
