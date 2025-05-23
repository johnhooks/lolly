import React from 'react';

import {
    Button,
    ToggleControl,
    Notice,
    __experimentalHStack as HStack,
    __experimentalVStack as VStack,
} from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import { useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import { SETTINGS_KEY } from '../constants';
import { default as settingStore } from '../store';

import HttpRedactionList from './http-redaction-list';

export default function SettingsPage(): React.ReactNode {
    const { settings, isSaving, message, isEnabled } = useSelect(
        (select) => ({
            settings: select(settingStore).getSettings(),
            isSaving: select(settingStore).isSaving(),
            message: select(settingStore).getMessage(),
            isEnabled: select(settingStore).isEnabled(),
        }),
        []
    );

    const { loadSettings, saveSettings, updateSetting, setMessage } =
        useDispatch(settingStore);

    useEffect(() => {
        loadSettings(SETTINGS_KEY);
    }, [loadSettings]);

    const handleSave = (): void => {
        saveSettings(SETTINGS_KEY);
    };

    if (!settings) {
        return null;
    }

    return (
        <VStack spacing={4} style={{ maxWidth: '800px' }}>
            <h1>{__('Dozuki Log Settings', 'dozuki')}</h1>
            {message && (
                <Notice
                    status={message.type}
                    isDismissible={true}
                    onRemove={() => setMessage(null)}
                >
                    {message.content}
                </Notice>
            )}
            <div className="dozuki-settings__panel">
                <ToggleControl
                    label={__('Enable', 'dozuki')}
                    checked={settings.enabled}
                    onChange={(value: boolean) =>
                        updateSetting('enabled', value)
                    }
                    help={__('Enable Dozuki logging.', 'dozuki')}
                    __nextHasNoMarginBottom
                />
                <ToggleControl
                    label={__('REST API Logging', 'dozuki')}
                    checked={settings.wp_rest_logging_enabled}
                    onChange={(value: boolean) =>
                        updateSetting('wp_rest_logging_enabled', value)
                    }
                    disabled={!isEnabled}
                    help={__('Enable WordPress REST API logging.', 'dozuki')}
                    __nextHasNoMarginBottom
                />
                <ToggleControl
                    label={__('HTTP Client Logging', 'dozuki')}
                    checked={settings.wp_http_client_logging_enabled}
                    onChange={(value: boolean) =>
                        updateSetting('wp_http_client_logging_enabled', value)
                    }
                    disabled={!isEnabled}
                    help={__('Enable WordPress HTTP client logging.', 'dozuki')}
                    __nextHasNoMarginBottom
                />
                <ToggleControl
                    label={__('HTTP Redactions', 'dozuki')}
                    checked={settings.http_redactions_enabled}
                    onChange={(value: boolean) =>
                        updateSetting('http_redactions_enabled', value)
                    }
                    disabled={!isEnabled}
                    help={__('Enable the HTTP Redactions feature.', 'dozuki')}
                    __nextHasNoMarginBottom
                />
                <ToggleControl
                    label={__('HTTP Whitelist', 'dozuki')}
                    checked={settings.http_whitelist_enabled}
                    onChange={(value: boolean) =>
                        updateSetting('http_whitelist_enabled', value)
                    }
                    disabled={!isEnabled}
                    help={__('Enable the HTTP Whitelist feature.', 'dozuki')}
                    __nextHasNoMarginBottom
                />
            </div>

            {isEnabled && settings.http_redactions_enabled && (
                <HttpRedactionList />
            )}

            <HStack>
                <Button
                    isPrimary
                    isBusy={isSaving}
                    disabled={isSaving}
                    onClick={handleSave}
                >
                    {__('Save Settings', 'dozuki')}
                </Button>
            </HStack>
        </VStack>
    );
}
