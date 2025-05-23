import apiFetch from '@wordpress/api-fetch';

import { setSettings, setSaving, setMessage } from './actions';
import type { Settings } from './types';

export const saveSettings = (settingsKey: string) => {
    return async ({ dispatch, select }: any) => {
        dispatch(setSaving(true));
        dispatch(setMessage(null));

        try {
            const settings: Settings = select.getSettings();
            await apiFetch({
                path: '/wp/v2/settings',
                method: 'POST',
                data: { [settingsKey]: settings },
            });

            dispatch(
                setMessage({
                    type: 'success',
                    content: 'Settings saved successfully',
                })
            );
        } catch (error: any) {
            dispatch(
                setMessage({
                    type: 'error',
                    content: error.message || 'Failed to save settings',
                })
            );
        } finally {
            dispatch(setSaving(false));
        }
    };
};

export const loadSettings = (settingsKey: string) => {
    return async ({ dispatch }: any) => {
        try {
            const response: { [key: string]: Settings } = await apiFetch({
                path: '/wp/v2/settings',
            });

            if (!response[settingsKey]) {
                dispatch(
                    setMessage({
                        type: 'error',
                        content: 'Failed to load settings.',
                    })
                );
            } else {
                dispatch(setSettings(response[settingsKey]));
            }
        } catch (error: any) {
            dispatch(
                setMessage({
                    type: 'error',
                    content: error.message || 'Failed to load settings.',
                })
            );
        }
    };
};

export default {
    saveSettings,
    loadSettings,
};
