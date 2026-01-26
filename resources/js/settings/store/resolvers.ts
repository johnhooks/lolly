import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';

import type { Settings, WpRestApiError } from '../../types';
import { isWpRestApiError, forwardResolver } from '../../utils';

import type { DropinStatus, SettingsThunk } from './types';

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

export const getDropinStatus =
    (): SettingsThunk =>
    async ({ dispatch }) => {
        dispatch({
            type: 'FETCH_DROPIN_STATUS_START',
        });

        try {
            const status = await apiFetch<DropinStatus>({
                path: '/lolly/v1/settings/dropin',
            });

            dispatch({
                type: 'FETCH_DROPIN_STATUS_FINISHED',
                status,
            });
        } catch (e: unknown) {
            const error: WpRestApiError = isWpRestApiError(e)
                ? e
                : {
                      code: 'lolly.fetch-dropin-status-failed',
                      message: __(
                          'Failed to fetch drop-in status, an unknown error occurred.',
                          'lolly'
                      ),
                  };

            dispatch({
                type: 'FETCH_DROPIN_STATUS_FAILED',
                error,
            });
        }
    };
