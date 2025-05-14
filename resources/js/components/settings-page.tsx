import React from 'react';

import apiFetch from '@wordpress/api-fetch';
import {
    Button,
    ToggleControl,
    Notice,
    __experimentalHStack as HStack,
    __experimentalVStack as VStack,
} from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import { SETTINGS_KEY } from '../constants';

interface Settings {
    enabled: boolean;
    http_redactions_enabled: boolean;
    http_whitelist_enabled: boolean;
    wp_rest_logging_enabled: boolean;
    wp_http_client_logging_enabled: boolean;
    http_redactions: string[];
    http_whitelist: string[];
}

interface Message {
    type: 'success' | 'error' | 'info' | 'warning';
    content: string;
}

const DEFAULT_SETTINGS: Settings = {
    enabled: false,
    http_redactions_enabled: true,
    http_whitelist_enabled: false,
    wp_rest_logging_enabled: true,
    wp_http_client_logging_enabled: true,
    http_redactions: [],
    http_whitelist: [],
};

export default function SettingsPage(): React.ReactNode {
    const [settings, setSettings] = useState<Settings>(DEFAULT_SETTINGS);
    const [isSaving, setIsSaving] = useState<boolean>(false);
    const [message, setMessage] = useState<Message | null>(null);

    useEffect(() => {
        apiFetch<{ dozuki_settings?: Settings }>({ path: '/wp/v2/settings' })
            .then((response) => {
                if (!response.dozuki_settings) {
                    setMessage({
                        type: 'error',
                        content: __('Failed to load settings.', 'dozuki'),
                    });
                } else {
                    setSettings(response[SETTINGS_KEY]);
                }
            })
            .catch((error: { message?: string }) => {
                setMessage({
                    type: 'error',
                    content:
                        error.message ||
                        __('Failed to load settings.', 'dozuki'),
                });
            });
    }, []);

    const isEnabled = settings?.enabled ?? DEFAULT_SETTINGS.enabled;

    const handleSave = (): void => {
        setIsSaving(true);
        setMessage(null);

        apiFetch({
            path: '/wp/v2/settings',
            method: 'POST',
            data: { [SETTINGS_KEY]: settings },
        })
            .then(() => {
                setMessage({
                    type: 'success',
                    content: __('Settings saved successfully', 'dozuki'),
                });
            })
            .catch((error: { message?: string }) => {
                setMessage({
                    type: 'error',
                    content:
                        error.message ||
                        __('Failed to save settings', 'dozuki'),
                });
            })
            .finally(() => {
                setIsSaving(false);
            });
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
                        setSettings({
                            ...settings,
                            enabled: value,
                        })
                    }
                    help={__('Enable Dozuki logging.', 'dozuki')}
                    __nextHasNoMarginBottom
                />
                <ToggleControl
                    label={__('REST API Logging', 'dozuki')}
                    checked={settings.wp_rest_logging_enabled}
                    onChange={(value: boolean) =>
                        setSettings({
                            ...settings,
                            wp_rest_logging_enabled: value,
                        })
                    }
                    disabled={!isEnabled}
                    help={__('Enable WordPress REST API logging.', 'dozuki')}
                    __nextHasNoMarginBottom
                />
                <ToggleControl
                    label={__('HTTP Client Logging', 'dozuki')}
                    checked={settings.wp_http_client_logging_enabled}
                    onChange={(value: boolean) =>
                        setSettings({
                            ...settings,
                            wp_http_client_logging_enabled: value,
                        })
                    }
                    disabled={!isEnabled}
                    help={__('Enable WordPress HTTP client logging.', 'dozuki')}
                    __nextHasNoMarginBottom
                />
                <ToggleControl
                    label={__('HTTP Redactions', 'dozuki')}
                    checked={settings.http_redactions_enabled}
                    onChange={(value: boolean) =>
                        setSettings({
                            ...settings,
                            http_redactions_enabled: value,
                        })
                    }
                    disabled={!isEnabled}
                    help={__('Enable the HTTP Redactions feature.', 'dozuki')}
                    __nextHasNoMarginBottom
                />
                <ToggleControl
                    label={__('HTTP Whitelist', 'dozuki')}
                    checked={settings.http_whitelist_enabled}
                    onChange={(value: boolean) =>
                        setSettings({
                            ...settings,
                            http_whitelist_enabled: value,
                        })
                    }
                    disabled={!isEnabled}
                    help={__('Enable the HTTP Whitelist feature.', 'dozuki')}
                    __nextHasNoMarginBottom
                />
            </div>
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
