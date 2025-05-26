import React, { useState } from 'react';

import {
    Button,
    Modal,
    TextControl,
    CheckboxControl,
    __experimentalHStack as HStack,
    __experimentalVStack as VStack,
} from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

import { default as settingStore } from '../store';

interface AddWhitelistHostButtonProps {
    variant?: 'primary' | 'secondary' | 'tertiary';
    size?: 'small' | 'compact' | 'default';
}

export default function AddWhitelistHostButton({
    variant = 'primary',
    size = 'default',
}: AddWhitelistHostButtonProps) {
    const [isModalOpen, setIsModalOpen] = useState(false);

    const whitelistSets = useSelect((select) => {
        return select(settingStore).getHttpWhitelist();
    }, []);

    const {
        addWhitelistHost,
        updateWhitelistHost,
        updateWhitelistHostGlob,
        addWhitelistPath,
        updateWhitelistPath,
    } = useDispatch(settingStore);

    const handleAddHost = (formData: {
        host: string;
        hostGlob: boolean;
        path: string;
        pathGlob: boolean;
    }) => {
        // Add the host first
        addWhitelistHost();

        // Add the path after a brief delay to ensure the host is created
        setTimeout(() => {
            const newHostIndex = whitelistSets.length;

            // Update the host name and glob setting
            updateWhitelistHost(newHostIndex, formData.host);
            updateWhitelistHostGlob(newHostIndex, formData.hostGlob);

            // Add the first path
            addWhitelistPath(newHostIndex);

            // Update the path details
            setTimeout(() => {
                updateWhitelistPath(newHostIndex, 0, 'path', formData.path);
                updateWhitelistPath(newHostIndex, 0, 'glob', formData.pathGlob);
            }, 0);
        }, 0);

        setIsModalOpen(false);
    };

    return (
        <>
            <Button
                variant={variant}
                size={size}
                onClick={() => setIsModalOpen(true)}
            >
                {__('Add Host', 'lolly')}
            </Button>

            {isModalOpen && (
                <AddWhitelistHostModal
                    onSave={handleAddHost}
                    onCancel={() => setIsModalOpen(false)}
                />
            )}
        </>
    );
}

interface AddWhitelistHostModalProps {
    onSave: (data: {
        host: string;
        hostGlob: boolean;
        path: string;
        pathGlob: boolean;
    }) => void;
    onCancel: () => void;
}

function AddWhitelistHostModal({
    onSave,
    onCancel,
}: AddWhitelistHostModalProps) {
    const [host, setHost] = useState('');
    const [hostGlob, setHostGlob] = useState(false);
    const [path, setPath] = useState('');
    const [pathGlob, setPathGlob] = useState(false);

    const handleSave = () => {
        onSave({
            host,
            hostGlob,
            path,
            pathGlob,
        });
    };

    const isValid = host.trim() !== '' && path.trim() !== '';

    return (
        <Modal
            title={__('Add Whitelist Host Configuration', 'lolly')}
            onRequestClose={onCancel}
            size="medium"
        >
            <VStack spacing={4}>
                <div>
                    <h3
                        style={{
                            margin: '0 0 16px 0',
                            fontSize: '14px',
                            fontWeight: 600,
                        }}
                    >
                        {__('Host Settings', 'lolly')}
                    </h3>
                    <VStack spacing={3}>
                        <TextControl
                            label={__('Host', 'lolly')}
                            value={host}
                            onChange={setHost}
                            placeholder="api.example.com, *.example.com"
                            help={__(
                                'The hostname or domain pattern to match for whitelisting',
                                'lolly'
                            )}
                            __nextHasNoMarginBottom
                        />

                        <CheckboxControl
                            label={__(
                                'Use Host Glob Pattern Matching',
                                'lolly'
                            )}
                            checked={hostGlob}
                            onChange={setHostGlob}
                            help={__(
                                'If checked, wildcards like * will be interpreted as patterns for the host',
                                'lolly'
                            )}
                            __nextHasNoMarginBottom
                        />
                    </VStack>
                </div>

                <div>
                    <h3
                        style={{
                            margin: '0 0 16px 0',
                            fontSize: '14px',
                            fontWeight: 600,
                        }}
                    >
                        {__('First Path Configuration', 'lolly')}
                    </h3>
                    <VStack spacing={3}>
                        <TextControl
                            label={__('Path', 'lolly')}
                            value={path}
                            onChange={setPath}
                            placeholder="/api/v1/users, /admin/*, /wp-json/**"
                            help={__(
                                'The URL path or pattern to match for whitelisting',
                                'lolly'
                            )}
                            __nextHasNoMarginBottom
                        />

                        <CheckboxControl
                            label={__(
                                'Use Path Glob Pattern Matching',
                                'lolly'
                            )}
                            checked={pathGlob}
                            onChange={setPathGlob}
                            help={__(
                                'If checked, wildcards like * and ** will be interpreted as patterns for the path',
                                'lolly'
                            )}
                            __nextHasNoMarginBottom
                        />
                    </VStack>
                </div>

                <HStack justify="right">
                    <Button variant="tertiary" onClick={onCancel}>
                        {__('Cancel', 'lolly')}
                    </Button>
                    <Button
                        variant="primary"
                        onClick={handleSave}
                        disabled={!isValid}
                    >
                        {__('Add Host Configuration', 'lolly')}
                    </Button>
                </HStack>
            </VStack>
        </Modal>
    );
}
