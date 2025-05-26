import React, { useState } from 'react';

import {
    Button,
    Modal,
    TextControl,
    SelectControl,
    CheckboxControl,
    __experimentalHStack as HStack,
    __experimentalVStack as VStack,
} from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

import { default as settingStore } from '../store';

const REDACTION_TYPES = [
    { label: __('All (*)', 'lolly'), value: '*' },
    { label: __('Query Params', 'lolly'), value: 'query' },
    { label: __('Headers', 'lolly'), value: 'header' },
    { label: __('Request Body', 'lolly'), value: 'request' },
    { label: __('Response Body', 'lolly'), value: 'response' },
];

interface AddHostButtonProps {
    variant?: 'primary' | 'secondary' | 'tertiary';
    size?: 'small' | 'compact' | 'default';
}

export default function AddHostButton({
    variant = 'primary',
    size = 'default',
}: AddHostButtonProps) {
    const [isModalOpen, setIsModalOpen] = useState(false);

    const redactionSets = useSelect((select) => {
        return select(settingStore).getHttpRedactions();
    }, []);

    const { addHost, updateHost, addPath, updatePath, updateRedactions } =
        useDispatch(settingStore);

    const handleAddHost = (formData: {
        host: string;
        path: string;
        glob: boolean;
        redactionType: string;
        redactionValue: string;
        redactionRemove: boolean;
    }) => {
        // Add the host first
        addHost();

        // Add the path and redaction after a brief delay to ensure the host is created
        setTimeout(() => {
            const newHostIndex = redactionSets.length;

            // Update the host name
            updateHost(newHostIndex, formData.host);

            // Add the first path
            addPath(newHostIndex);

            // Update the path details
            setTimeout(() => {
                updatePath(newHostIndex, 0, 'path', formData.path);
                updatePath(newHostIndex, 0, 'glob', formData.glob);

                // Add the first redaction
                const firstRedaction = {
                    type: formData.redactionType,
                    value: formData.redactionValue,
                    remove: formData.redactionRemove,
                };
                updateRedactions(newHostIndex, 0, [firstRedaction]);
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
                <AddHostModal
                    onSave={handleAddHost}
                    onCancel={() => setIsModalOpen(false)}
                />
            )}
        </>
    );
}

interface AddHostModalProps {
    onSave: (data: {
        host: string;
        path: string;
        glob: boolean;
        redactionType: string;
        redactionValue: string;
        redactionRemove: boolean;
    }) => void;
    onCancel: () => void;
}

function AddHostModal({ onSave, onCancel }: AddHostModalProps) {
    const [host, setHost] = useState('');
    const [path, setPath] = useState('');
    const [glob, setGlob] = useState(false);
    const [redactionType, setRedactionType] = useState('*');
    const [redactionValue, setRedactionValue] = useState('');
    const [redactionRemove, setRedactionRemove] = useState(false);

    const handleSave = () => {
        onSave({
            host,
            path,
            glob,
            redactionType,
            redactionValue,
            redactionRemove,
        });
    };

    const isValid =
        host.trim() !== '' &&
        path.trim() !== '' &&
        redactionValue.trim() !== '';

    return (
        <Modal
            title={__('Add Host Configuration', 'lolly')}
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
                    <TextControl
                        label={__('Host', 'lolly')}
                        value={host}
                        onChange={setHost}
                        placeholder="api.example.com, *.example.com"
                        help={__(
                            'The hostname or domain pattern to match',
                            'lolly'
                        )}
                        __nextHasNoMarginBottom
                    />
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
                                'The URL path or pattern to match',
                                'lolly'
                            )}
                            __nextHasNoMarginBottom
                        />

                        <CheckboxControl
                            label={__('Use Glob Pattern Matching', 'lolly')}
                            checked={glob}
                            onChange={setGlob}
                            help={__(
                                'If checked, wildcards like * and ** will be interpreted as patterns',
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
                        {__('First Redaction Rule', 'lolly')}
                    </h3>
                    <VStack spacing={3}>
                        <SelectControl
                            label={__('Redaction Type', 'lolly')}
                            value={redactionType}
                            options={REDACTION_TYPES}
                            onChange={setRedactionType}
                            help={__(
                                'Choose what type of data to redact',
                                'lolly'
                            )}
                            __nextHasNoMarginBottom
                        />

                        <TextControl
                            label={__('Value to Match', 'lolly')}
                            value={redactionValue}
                            onChange={setRedactionValue}
                            placeholder="password, token, api_key, etc."
                            help={__(
                                'The parameter/header name or content to redact',
                                'lolly'
                            )}
                            __nextHasNoMarginBottom
                        />

                        <CheckboxControl
                            label={__('Remove Property Entirely', 'lolly')}
                            checked={redactionRemove}
                            onChange={setRedactionRemove}
                            help={__(
                                'If checked, the property will be removed completely instead of being redacted',
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
