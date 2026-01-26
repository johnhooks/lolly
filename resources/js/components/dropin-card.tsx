import React from 'react';

import {
    Button,
    Card,
    CardBody,
    CardHeader,
    Notice,
    Spinner,
    __experimentalVStack as VStack,
    __experimentalText as Text,
} from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import { __, sprintf } from '@wordpress/i18n';

import { store as settingsStore } from '../settings/store';

export default function DropinCard(): React.ReactNode {
    const { status, isBusy, isInstalling, isUninstalling, error } = useSelect(
        (select) => ({
            status: select(settingsStore).getDropinStatus(),
            isBusy: select(settingsStore).isDropinBusy(),
            isInstalling: select(settingsStore).isDropinInstalling(),
            isUninstalling: select(settingsStore).isDropinUninstalling(),
            error: select(settingsStore).getDropinError(),
        }),
        []
    );

    const { installDropin, uninstallDropin, clearDropinError } =
        useDispatch(settingsStore);

    // Status is still loading
    if (status === undefined) {
        return (
            <Card>
                <CardHeader>
                    <h2 style={{ margin: 0 }}>
                        {__('Fatal Error Handler', 'lolly')}
                    </h2>
                </CardHeader>
                <CardBody>
                    <Spinner />
                </CardBody>
            </Card>
        );
    }

    const isLollyInstalled = status.installed && status.is_lolly;
    const isThirdParty = status.installed && !status.is_lolly;

    return (
        <Card>
            <CardHeader>
                <h2 style={{ margin: 0 }}>
                    {__('Fatal Error Handler', 'lolly')}
                </h2>
            </CardHeader>
            <CardBody>
                <VStack spacing={4}>
                    <Text>
                        {__(
                            'Install the fatal error handler drop-in for enhanced error logging with backtraces, extension detection, and recovery mode integration.',
                            'lolly'
                        )}
                    </Text>

                    {error && (
                        <Notice
                            status="error"
                            isDismissible={true}
                            onDismiss={clearDropinError}
                        >
                            {error.message}
                        </Notice>
                    )}

                    {!status.writable && (
                        <Notice status="warning" isDismissible={false}>
                            {__(
                                'The wp-content directory is not writable. Cannot install or uninstall the drop-in.',
                                'lolly'
                            )}
                        </Notice>
                    )}

                    {isThirdParty && (
                        <Notice status="warning" isDismissible={false}>
                            {__(
                                'A fatal error handler from another plugin is already installed. Uninstall it first to use the Lolly handler.',
                                'lolly'
                            )}
                        </Notice>
                    )}

                    {isLollyInstalled && (
                        <Notice status="success" isDismissible={false}>
                            {status.version
                                ? sprintf(
                                      /* translators: %s: version number */
                                      __(
                                          'Lolly fatal error handler v%s is installed.',
                                          'lolly'
                                      ),
                                      status.version
                                  )
                                : __(
                                      'Lolly fatal error handler is installed.',
                                      'lolly'
                                  )}
                        </Notice>
                    )}

                    {!status.installed && status.writable && (
                        <Notice status="info" isDismissible={false}>
                            {__(
                                'The drop-in is not installed. Basic error logging via shutdown hook is active.',
                                'lolly'
                            )}
                        </Notice>
                    )}

                    <div>
                        {isLollyInstalled ? (
                            <Button
                                isDestructive
                                isBusy={isUninstalling}
                                disabled={isBusy || !status.writable}
                                onClick={uninstallDropin}
                            >
                                {isUninstalling
                                    ? __('Uninstalling\u2026', 'lolly')
                                    : __('Uninstall Drop-in', 'lolly')}
                            </Button>
                        ) : (
                            <Button
                                variant="secondary"
                                isBusy={isInstalling}
                                disabled={
                                    isBusy || !status.writable || isThirdParty
                                }
                                onClick={installDropin}
                            >
                                {isInstalling
                                    ? __('Installing\u2026', 'lolly')
                                    : __('Install Drop-in', 'lolly')}
                            </Button>
                        )}
                    </div>
                </VStack>
            </CardBody>
        </Card>
    );
}
