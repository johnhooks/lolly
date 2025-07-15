import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';

import { SETTINGS_KEY } from '../../constants';
import type { Settings } from '../../store/types';
import { WpRestApiError } from '../../types';
import { isWpRestApiError } from '../../utils';

import type { SettingsThunk } from './types';

export const getSettings =
    (): SettingsThunk =>
    async ({ dispatch }) => {
        try {
            const response = await apiFetch<Record<string, unknown>>({
                path: '/wp/v2/settings',
            });

            // We don't have to validate the server response here... do we?
            const settings = response?.[SETTINGS_KEY] as Settings | undefined;

            if (!settings) {
                dispatch({
                    type: 'FETCH_SETTINGS_FAILED',
                    error: {
                        code: 'lolly.fetch-settings-failed',
                        message: __(
                            'Failed to fetch Lolly settings, the server response is missing the Lolly settings value.',
                            'lolly'
                        ),
                    },
                });

                return;
            }

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

            // @todo Should we just throw the error here? We could catch it in an error boundary.
            // Could be handy if we want to use `useSuspenseSelect`.
            dispatch({
                type: 'FETCH_SETTINGS_FAILED',
                error,
            });
        }
    };
