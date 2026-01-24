import React, { useCallback } from 'react';

import {
    Button,
    ToggleControl,
    Notice,
    Card,
    CardBody,
    CardHeader,
    __experimentalHStack as HStack,
    __experimentalVStack as VStack,
} from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

import { store as settingStore } from '../settings/store';
import type { AuthLoggingConfig } from '../types';

const defaultAuthConfig: AuthLoggingConfig = {
    login: true,
    logout: true,
    login_failed: false,
    password_changed: true,
    app_password_created: true,
    app_password_deleted: true,
};

export default function SettingsPage(): React.ReactNode {
    const { settings, isSaving, editError } = useSelect(
        (select) => ({
            settings: select(settingStore).getEditedSettings(),
            isSaving: select(settingStore).isSaving(),
            editError: select(settingStore).getEditError(),
        }),
        []
    );

    const { editSetting, saveEditedSettings } = useDispatch(settingStore);

    const editAuthConfig = useCallback(
        (key: keyof AuthLoggingConfig, value: boolean) => {
            const currentConfig =
                settings?.wp_auth_logging_config ?? defaultAuthConfig;
            editSetting('wp_auth_logging_config', {
                ...currentConfig,
                [key]: value,
            });
        },
        [settings?.wp_auth_logging_config, editSetting]
    );

    // Note: The Lolly settings are preloaded, though still has to go through
    // resolution because `apiFetch` returns a promise.
    if (!settings) {
        return null;
    }

    const authConfig = settings.wp_auth_logging_config ?? defaultAuthConfig;
    const authEventsDisabled =
        !settings.enabled || !settings.wp_auth_logging_enabled;

    return (
        <VStack spacing={4}>
            <h1>{__('Lolly Log Settings', 'lolly')}</h1>
            {editError && (
                <Notice status="error" isDismissible={true}>
                    {editError.message}
                </Notice>
            )}
            <Card>
                <CardHeader>
                    <h2 style={{ margin: 0 }}>
                        {__('Main Settings', 'lolly')}
                    </h2>
                </CardHeader>
                <CardBody>
                    <VStack spacing={4}>
                        <ToggleControl
                            label={__('Enable', 'lolly')}
                            checked={settings.enabled}
                            onChange={(value: boolean) =>
                                editSetting('enabled', value)
                            }
                            help={__('Enable Lolly logging.', 'lolly')}
                            __nextHasNoMarginBottom
                        />
                        <ToggleControl
                            label={__('REST API Logging', 'lolly')}
                            checked={settings.wp_rest_logging_enabled}
                            onChange={(value: boolean) =>
                                editSetting('wp_rest_logging_enabled', value)
                            }
                            disabled={!settings.enabled}
                            help={__(
                                'Enable WordPress REST API logging.',
                                'lolly'
                            )}
                            __nextHasNoMarginBottom
                        />
                        <ToggleControl
                            label={__('HTTP Client Logging', 'lolly')}
                            checked={settings.wp_http_client_logging_enabled}
                            onChange={(value: boolean) =>
                                editSetting(
                                    'wp_http_client_logging_enabled',
                                    value
                                )
                            }
                            disabled={!settings.enabled}
                            help={__(
                                'Enable WordPress HTTP client logging.',
                                'lolly'
                            )}
                            __nextHasNoMarginBottom
                        />
                        <ToggleControl
                            label={__('User Event Logging', 'lolly')}
                            checked={settings.wp_user_event_logging_enabled}
                            onChange={(value: boolean) =>
                                editSetting(
                                    'wp_user_event_logging_enabled',
                                    value
                                )
                            }
                            disabled={!settings.enabled}
                            help={__(
                                'Log user creation, deletion, and role changes.',
                                'lolly'
                            )}
                            __nextHasNoMarginBottom
                        />
                        <ToggleControl
                            label={__('Authentication Logging', 'lolly')}
                            checked={settings.wp_auth_logging_enabled}
                            onChange={(value: boolean) =>
                                editSetting('wp_auth_logging_enabled', value)
                            }
                            disabled={!settings.enabled}
                            help={__(
                                'Log authentication events. Configure which events below.',
                                'lolly'
                            )}
                            __nextHasNoMarginBottom
                        />
                        <ToggleControl
                            label={__('HTTP Redactions', 'lolly')}
                            checked={settings.http_redactions_enabled}
                            onChange={(value: boolean) =>
                                editSetting('http_redactions_enabled', value)
                            }
                            disabled={!settings.enabled}
                            help={__(
                                'Enable the HTTP Redactions feature.',
                                'lolly'
                            )}
                            __nextHasNoMarginBottom
                        />
                        <ToggleControl
                            label={__('HTTP Whitelist', 'lolly')}
                            checked={settings.http_whitelist_enabled}
                            onChange={(value: boolean) =>
                                editSetting('http_whitelist_enabled', value)
                            }
                            disabled={!settings.enabled}
                            help={__(
                                'Enable the HTTP Whitelist feature.',
                                'lolly'
                            )}
                            __nextHasNoMarginBottom
                        />
                    </VStack>
                </CardBody>
            </Card>

            {settings.wp_auth_logging_enabled && (
                <Card>
                    <CardHeader>
                        <h2 style={{ margin: 0 }}>
                            {__('Authentication Events', 'lolly')}
                        </h2>
                    </CardHeader>
                    <CardBody>
                        <VStack spacing={4}>
                            <ToggleControl
                                label={__('Login', 'lolly')}
                                checked={authConfig.login}
                                onChange={(value: boolean) =>
                                    editAuthConfig('login', value)
                                }
                                disabled={authEventsDisabled}
                                help={__(
                                    'Log successful user logins.',
                                    'lolly'
                                )}
                                __nextHasNoMarginBottom
                            />
                            <ToggleControl
                                label={__('Logout', 'lolly')}
                                checked={authConfig.logout}
                                onChange={(value: boolean) =>
                                    editAuthConfig('logout', value)
                                }
                                disabled={authEventsDisabled}
                                help={__('Log user logouts.', 'lolly')}
                                __nextHasNoMarginBottom
                            />
                            <ToggleControl
                                label={__('Login Failed', 'lolly')}
                                checked={authConfig.login_failed}
                                onChange={(value: boolean) =>
                                    editAuthConfig('login_failed', value)
                                }
                                disabled={authEventsDisabled}
                                help={__(
                                    'Log failed login attempts. May generate high volume on sites under attack.',
                                    'lolly'
                                )}
                                __nextHasNoMarginBottom
                            />
                            <ToggleControl
                                label={__('Password Changed', 'lolly')}
                                checked={authConfig.password_changed}
                                onChange={(value: boolean) =>
                                    editAuthConfig('password_changed', value)
                                }
                                disabled={authEventsDisabled}
                                help={__(
                                    'Log password changes and resets.',
                                    'lolly'
                                )}
                                __nextHasNoMarginBottom
                            />
                            <ToggleControl
                                label={__(
                                    'Application Password Created',
                                    'lolly'
                                )}
                                checked={authConfig.app_password_created}
                                onChange={(value: boolean) =>
                                    editAuthConfig(
                                        'app_password_created',
                                        value
                                    )
                                }
                                disabled={authEventsDisabled}
                                help={__(
                                    'Log when application passwords are created.',
                                    'lolly'
                                )}
                                __nextHasNoMarginBottom
                            />
                            <ToggleControl
                                label={__(
                                    'Application Password Deleted',
                                    'lolly'
                                )}
                                checked={authConfig.app_password_deleted}
                                onChange={(value: boolean) =>
                                    editAuthConfig(
                                        'app_password_deleted',
                                        value
                                    )
                                }
                                disabled={authEventsDisabled}
                                help={__(
                                    'Log when application passwords are deleted.',
                                    'lolly'
                                )}
                                __nextHasNoMarginBottom
                            />
                        </VStack>
                    </CardBody>
                </Card>
            )}

            <HStack>
                <Button
                    isPrimary
                    isBusy={isSaving}
                    disabled={isSaving}
                    onClick={saveEditedSettings}
                >
                    {__('Save Settings', 'lolly')}
                </Button>
            </HStack>
        </VStack>
    );
}
