import { combineReducers } from '@wordpress/data';

import {
    SET_SETTINGS,
    UPDATE_SETTING,
    UPDATE_HOST,
    REMOVE_HOST,
    ADD_HOST,
    UPDATE_PATH,
    REMOVE_PATH,
    ADD_PATH,
    UPDATE_REDACTIONS,
    UPDATE_WHITELIST_HOST,
    UPDATE_WHITELIST_HOST_GLOB,
    REMOVE_WHITELIST_HOST,
    ADD_WHITELIST_HOST,
    UPDATE_WHITELIST_PATH,
    REMOVE_WHITELIST_PATH,
    ADD_WHITELIST_PATH,
    SET_SAVING,
    SET_MESSAGE,
} from './actions';
import type { Settings } from './types';
import { DEFAULT_SETTINGS } from './types';

function settings(state = DEFAULT_SETTINGS, action: any): Settings {
    switch (action.type) {
        case SET_SETTINGS:
            return action.settings;

        case UPDATE_SETTING:
            return {
                ...state,
                [action.key]: action.value,
            };

        case UPDATE_HOST:
            return {
                ...state,
                http_redactions: state.http_redactions.map(
                    (redactionSet, index) =>
                        index === action.hostIndex
                            ? { ...redactionSet, host: action.host }
                            : redactionSet
                ),
            };

        case REMOVE_HOST:
            return {
                ...state,
                http_redactions: state.http_redactions.filter(
                    (_, index) => index !== action.hostIndex
                ),
            };

        case ADD_HOST:
            return {
                ...state,
                http_redactions: [
                    ...state.http_redactions,
                    { host: '', paths: [] },
                ],
            };

        case UPDATE_PATH:
            return {
                ...state,
                http_redactions: state.http_redactions.map(
                    (redactionSet, hostIdx) =>
                        hostIdx === action.hostIndex
                            ? {
                                  ...redactionSet,
                                  paths: redactionSet.paths.map(
                                      (path, pathIdx) =>
                                          pathIdx === action.pathIndex
                                              ? {
                                                    ...path,
                                                    [action.field]:
                                                        action.value,
                                                }
                                              : path
                                  ),
                              }
                            : redactionSet
                ),
            };

        case REMOVE_PATH:
            return {
                ...state,
                http_redactions: state.http_redactions.map(
                    (redactionSet, hostIdx) =>
                        hostIdx === action.hostIndex
                            ? {
                                  ...redactionSet,
                                  paths: redactionSet.paths.filter(
                                      (_, pathIdx) =>
                                          pathIdx !== action.pathIndex
                                  ),
                              }
                            : redactionSet
                ),
            };

        case ADD_PATH:
            return {
                ...state,
                http_redactions: state.http_redactions.map(
                    (redactionSet, hostIdx) =>
                        hostIdx === action.hostIndex
                            ? {
                                  ...redactionSet,
                                  paths: [
                                      ...redactionSet.paths,
                                      {
                                          path: '',
                                          redactions: [],
                                          glob: false,
                                      },
                                  ],
                              }
                            : redactionSet
                ),
            };

        case UPDATE_REDACTIONS:
            return {
                ...state,
                http_redactions: state.http_redactions.map(
                    (redactionSet, hostIdx) =>
                        hostIdx === action.hostIndex
                            ? {
                                  ...redactionSet,
                                  paths: redactionSet.paths.map(
                                      (path, pathIdx) =>
                                          pathIdx === action.pathIndex
                                              ? {
                                                    ...path,
                                                    redactions:
                                                        action.redactions,
                                                }
                                              : path
                                  ),
                              }
                            : redactionSet
                ),
            };

        case UPDATE_WHITELIST_HOST:
            return {
                ...state,
                http_whitelist: state.http_whitelist.map(
                    (whitelistSet, index) =>
                        index === action.hostIndex
                            ? { ...whitelistSet, host: action.host }
                            : whitelistSet
                ),
            };

        case UPDATE_WHITELIST_HOST_GLOB:
            return {
                ...state,
                http_whitelist: state.http_whitelist.map(
                    (whitelistSet, index) =>
                        index === action.hostIndex
                            ? { ...whitelistSet, glob: action.glob }
                            : whitelistSet
                ),
            };

        case REMOVE_WHITELIST_HOST:
            return {
                ...state,
                http_whitelist: state.http_whitelist.filter(
                    (_, index) => index !== action.hostIndex
                ),
            };

        case ADD_WHITELIST_HOST:
            return {
                ...state,
                http_whitelist: [
                    ...state.http_whitelist,
                    { host: '', paths: [], glob: false },
                ],
            };

        case UPDATE_WHITELIST_PATH:
            return {
                ...state,
                http_whitelist: state.http_whitelist.map(
                    (whitelistSet, hostIdx) =>
                        hostIdx === action.hostIndex
                            ? {
                                  ...whitelistSet,
                                  paths: whitelistSet.paths.map(
                                      (path, pathIdx) =>
                                          pathIdx === action.pathIndex
                                              ? {
                                                    ...path,
                                                    [action.field]:
                                                        action.value,
                                                }
                                              : path
                                  ),
                              }
                            : whitelistSet
                ),
            };

        case REMOVE_WHITELIST_PATH:
            return {
                ...state,
                http_whitelist: state.http_whitelist.map(
                    (whitelistSet, hostIdx) =>
                        hostIdx === action.hostIndex
                            ? {
                                  ...whitelistSet,
                                  paths: whitelistSet.paths.filter(
                                      (_, pathIdx) =>
                                          pathIdx !== action.pathIndex
                                  ),
                              }
                            : whitelistSet
                ),
            };

        case ADD_WHITELIST_PATH:
            return {
                ...state,
                http_whitelist: state.http_whitelist.map(
                    (whitelistSet, hostIdx) =>
                        hostIdx === action.hostIndex
                            ? {
                                  ...whitelistSet,
                                  paths: [
                                      ...whitelistSet.paths,
                                      { path: '', glob: false },
                                  ],
                              }
                            : whitelistSet
                ),
            };

        default:
            return state;
    }
}

function isSaving(state = false, action: any): boolean {
    switch (action.type) {
        case SET_SAVING:
            return action.isSaving;
        default:
            return state;
    }
}

function message(state = null, action: any) {
    switch (action.type) {
        case SET_MESSAGE:
            return action.message;
        default:
            return state;
    }
}

export default combineReducers({
    settings,
    isSaving,
    message,
});
