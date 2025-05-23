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
    SET_SAVING,
    SET_MESSAGE,
} from './actions';
import type { State } from './types';
import { DEFAULT_STATE } from './types';

export default function reducer(state = DEFAULT_STATE, action: any): State {
    switch (action.type) {
        case SET_SETTINGS:
            return {
                ...state,
                settings: action.settings,
            };

        case UPDATE_SETTING:
            return {
                ...state,
                settings: {
                    ...state.settings,
                    [action.key]: action.value,
                },
            };

        case UPDATE_HOST:
            return {
                ...state,
                settings: {
                    ...state.settings,
                    http_redactions: state.settings.http_redactions.map(
                        (redactionSet, index) =>
                            index === action.hostIndex
                                ? { ...redactionSet, host: action.host }
                                : redactionSet
                    ),
                },
            };

        case REMOVE_HOST:
            return {
                ...state,
                settings: {
                    ...state.settings,
                    http_redactions: state.settings.http_redactions.filter(
                        (_, index) => index !== action.hostIndex
                    ),
                },
            };

        case ADD_HOST:
            return {
                ...state,
                settings: {
                    ...state.settings,
                    http_redactions: [
                        ...state.settings.http_redactions,
                        { host: '', paths: [] },
                    ],
                },
            };

        case UPDATE_PATH:
            return {
                ...state,
                settings: {
                    ...state.settings,
                    http_redactions: state.settings.http_redactions.map(
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
                },
            };

        case REMOVE_PATH:
            return {
                ...state,
                settings: {
                    ...state.settings,
                    http_redactions: state.settings.http_redactions.map(
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
                },
            };

        case ADD_PATH:
            return {
                ...state,
                settings: {
                    ...state.settings,
                    http_redactions: state.settings.http_redactions.map(
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
                },
            };

        case UPDATE_REDACTIONS:
            return {
                ...state,
                settings: {
                    ...state.settings,
                    http_redactions: state.settings.http_redactions.map(
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
                },
            };

        case SET_SAVING:
            return {
                ...state,
                isSaving: action.isSaving,
            };

        case SET_MESSAGE:
            return {
                ...state,
                message: action.message,
            };

        default:
            return state;
    }
}
