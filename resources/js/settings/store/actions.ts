import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';

import { Settings, WpRestApiError } from '../../types';
import { isWpRestApiError } from '../../utils';

import { Action, DropinStatus, SettingsThunk } from './types';

/**
 * Edit a settings property.
 *
 * Intended for setting simple settings values, like the boolean
 * flags.
 */
export function editSetting<K extends keyof Settings>(
    property: K,
    value: Settings[K]
): Action {
    return {
        type: 'EDIT_SETTINGS_RECORD',
        edits: {
            [property]: value,
        },
    };
}

export const saveEditedSettings =
    (): SettingsThunk =>
    async ({ dispatch, select }) => {
        const edits = select.getEdits();

        if (Object.keys(edits).length === 0) {
            dispatch({
                type: 'SAVE_SETTINGS_RECORD_FAILED',
                error: {
                    code: 'lolly.save-settings-failed',
                    message: __(
                        'Failed to save Lolly settings, there are currently no edits.',
                        'lolly'
                    ),
                },
            });

            return;
        }

        const combined = { ...select.getSettings(), ...edits };

        dispatch({
            type: 'SAVE_SETTINGS_RECORD_START',
        });

        try {
            const settings = await apiFetch<Settings>({
                path: '/lolly/v1/settings',
                method: 'PUT',
                data: combined,
            });

            dispatch({
                type: 'SAVE_SETTINGS_RECORD_FINISHED',
                settings,
            });
        } catch (e: unknown) {
            const error: WpRestApiError = isWpRestApiError(e)
                ? e
                : {
                      code: 'lolly.save-settings-failed',
                      message: __(
                          'Failed to save Lolly settings, an unknown error occurred.',
                          'lolly'
                      ),
                  };

            dispatch({
                type: 'SAVE_SETTINGS_RECORD_FAILED',
                error,
            });
        }
    };

/**
 * Clear the drop-in error.
 */
export function clearDropinError(): Action {
    return {
        type: 'CLEAR_DROPIN_ERROR',
    };
}

/**
 * Install the drop-in.
 */
export const installDropin =
    (): SettingsThunk =>
    async ({ dispatch }) => {
        dispatch({
            type: 'INSTALL_DROPIN_START',
        });

        try {
            const status = await apiFetch<DropinStatus>({
                path: '/lolly/v1/settings/dropin',
                method: 'POST',
            });

            dispatch({
                type: 'INSTALL_DROPIN_FINISHED',
                status,
            });
        } catch (e: unknown) {
            const error: WpRestApiError = isWpRestApiError(e)
                ? e
                : {
                      code: 'lolly.install-dropin-failed',
                      message: __(
                          'Failed to install the drop-in, an unknown error occurred.',
                          'lolly'
                      ),
                  };

            dispatch({
                type: 'INSTALL_DROPIN_FAILED',
                error,
            });
        }
    };

/**
 * Uninstall the drop-in.
 */
export const uninstallDropin =
    (): SettingsThunk =>
    async ({ dispatch }) => {
        dispatch({
            type: 'UNINSTALL_DROPIN_START',
        });

        try {
            const status = await apiFetch<DropinStatus>({
                path: '/lolly/v1/settings/dropin',
                method: 'DELETE',
            });

            dispatch({
                type: 'UNINSTALL_DROPIN_FINISHED',
                status,
            });
        } catch (e: unknown) {
            const error: WpRestApiError = isWpRestApiError(e)
                ? e
                : {
                      code: 'lolly.uninstall-dropin-failed',
                      message: __(
                          'Failed to uninstall the drop-in, an unknown error occurred.',
                          'lolly'
                      ),
                  };

            dispatch({
                type: 'UNINSTALL_DROPIN_FAILED',
                error,
            });
        }
    };
