import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';

import { SETTINGS_KEY } from '../../constants';
import { Settings, WpRestApiError } from '../../types';
import { isWpRestApiError } from '../../utils';

import { Action, SettingsThunk } from './types';

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

        try {
            const response = await apiFetch<Record<string, unknown>>({
                path: '/wp/v2/settings',
                method: 'POST',
                data: { [SETTINGS_KEY]: combined },
            });

            // We don't have to validate the server response here... do we?
            const settings = response?.[SETTINGS_KEY] as Settings | undefined;

            if (!settings) {
                dispatch({
                    type: 'SAVE_SETTINGS_RECORD_FAILED',
                    error: {
                        code: 'lolly.save-settings-failed',
                        message: __(
                            'Failed to save Lolly settings, the server response is missing the Lolly settings value.',
                            'lolly'
                        ),
                    },
                });

                return;
            }

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
