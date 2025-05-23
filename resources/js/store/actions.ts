import type { Settings, RedactionItem, State } from './types';

export const SET_SETTINGS = 'SET_SETTINGS';
export const UPDATE_SETTING = 'UPDATE_SETTING';
export const UPDATE_HOST = 'UPDATE_HOST';
export const REMOVE_HOST = 'REMOVE_HOST';
export const ADD_HOST = 'ADD_HOST';
export const UPDATE_PATH = 'UPDATE_PATH';
export const REMOVE_PATH = 'REMOVE_PATH';
export const ADD_PATH = 'ADD_PATH';
export const UPDATE_REDACTIONS = 'UPDATE_REDACTIONS';
export const SET_SAVING = 'SET_SAVING';
export const SET_MESSAGE = 'SET_MESSAGE';

export const setSettings = (settings: Settings) => ({
    type: SET_SETTINGS,
    settings,
});

export const updateSetting = <K extends keyof Settings>(
    key: K,
    value: Settings[K]
) => ({
    type: UPDATE_SETTING,
    key,
    value,
});

export const updateHost = (hostIndex: number, host: string) => ({
    type: UPDATE_HOST,
    hostIndex,
    host,
});

export const removeHost = (hostIndex: number) => ({
    type: REMOVE_HOST,
    hostIndex,
});

export const addHost = () => ({
    type: ADD_HOST,
});

export const updatePath = (
    hostIndex: number,
    pathIndex: number,
    field: 'path' | 'glob',
    value: string | boolean
) => ({
    type: UPDATE_PATH,
    hostIndex,
    pathIndex,
    field,
    value,
});

export const removePath = (hostIndex: number, pathIndex: number) => ({
    type: REMOVE_PATH,
    hostIndex,
    pathIndex,
});

export const addPath = (hostIndex: number) => ({
    type: ADD_PATH,
    hostIndex,
});

export const updateRedactions = (
    hostIndex: number,
    pathIndex: number,
    redactions: RedactionItem[]
) => ({
    type: UPDATE_REDACTIONS,
    hostIndex,
    pathIndex,
    redactions,
});

export const setSaving = (isSaving: boolean) => ({
    type: SET_SAVING,
    isSaving,
});

export const setMessage = (message: State['message']) => ({
    type: SET_MESSAGE,
    message,
});

export default {
    setSettings,
    updateSetting,
    updateHost,
    removeHost,
    addHost,
    updatePath,
    removePath,
    addPath,
    updateRedactions,
    setSaving,
    setMessage,
};
