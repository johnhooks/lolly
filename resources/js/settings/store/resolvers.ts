import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';

import type { Settings, WpRestApiError } from '../../types';
import { isWpRestApiError, forwardResolver } from '../../utils';

import type { SettingsThunk } from './types';

export const getSettings =
    (): SettingsThunk =>
    async ({ dispatch }) => {
        try {
            const settings = await apiFetch<Settings>({
                path: '/lolly/v1/settings',
            });

            dispatch({
                type: 'FETCH_SETTINGS_FINISHED',
                settings,
            });
        } catch (e: unknown) {
            const error: WpRestApiError = isWpRestApiError(e)
                ? e
                : {
                      code: 'lolly.fetch-settings-failed',
                      message: __(
                          'Failed to fetch Lolly settings, an unknown error occurred.',
                          'lolly'
                      ),
                  };

            dispatch({
                type: 'FETCH_SETTINGS_FAILED',
                error,
            });
        }
    };

export const getEditedSettings = forwardResolver('getSettings');
