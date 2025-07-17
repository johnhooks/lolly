import React from 'react';

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

    // Note: The Lolly settings are preloaded, though still has to go through
    // resolution because `apiFetch` returns a promise.
    if (!settings) {
        return null;
    }

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
