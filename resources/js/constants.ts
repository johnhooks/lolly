import { WpRestApiError } from './types';

export const SETTINGS_KEY = 'lolly_settings';

export const DEFAULT_ERROR: WpRestApiError = {
    code: 'unknown',
    message: 'An unknown error occurred.',
};
