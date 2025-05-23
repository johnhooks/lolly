import React, { useState, useMemo } from 'react';

import {
    Button,
    Modal,
    TextControl,
    SelectControl,
    CheckboxControl,
    __experimentalHStack as HStack,
    __experimentalVStack as VStack,
} from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import {
    DataViews,
    filterSortAndPaginate,
    type View,
} from '@wordpress/dataviews';
import { __ } from '@wordpress/i18n';

import { default as settingStore } from '../store';
import type { RedactionItem } from '../store/types';

interface FlatRedactionItem {
    id: string;
    host: string;
    path: string;
    glob: boolean;
    type: string;
    value: string;
    remove: boolean;
    hostIndex: number;
    pathIndex: number;
    redactionIndex: number;
}

interface EditingPath {
    hostIndex: number;
    pathIndex: number;
    isNew: boolean;
}

interface EditingRedaction {
    hostIndex: number;
    pathIndex: number;
    redactionIndex?: number;
    isNew: boolean;
}

const REDACTION_TYPES = [
    { label: __('All (*)', 'dozuki'), value: '*' },
    { label: __('Query Params', 'dozuki'), value: 'query' },
    { label: __('Headers', 'dozuki'), value: 'header' },
    { label: __('Request Body', 'dozuki'), value: 'request' },
    { label: __('Response Body', 'dozuki'), value: 'response' },
];

export default function HttpRedactionOverview(): React.ReactNode {
    const redactionSets = useSelect((select) => {
        return select(settingStore).getHttpRedactions();
    }, []);

    const { updateHost, updatePath, addPath, updateRedactions } =
        useDispatch(settingStore);

    const [editingPath, setEditingPath] = useState<EditingPath | null>(null);
    const [editingRedaction, setEditingRedaction] =
        useState<EditingRedaction | null>(null);
    const [view, setView] = useState<View>({
        type: 'table',
        perPage: 50,
        page: 1,
        sort: {
            field: 'host',
            direction: 'desc',
        },
        search: '',
        filters: [],
        titleField: 'host',
        fields: ['path', 'type', 'value', 'remove'],
        layout: {},
    });

    // Flatten all redactions for DataViews
    const flatRedactions = useMemo(() => {
        const items: FlatRedactionItem[] = [];
        redactionSets.forEach((redactionSet, hostIndex) => {
            redactionSet.paths.forEach((path, pathIndex) => {
                path.redactions.forEach((redaction, redactionIndex) => {
                    items.push({
                        id: `${hostIndex}-${pathIndex}-${redactionIndex}`,
                        host:
                            redactionSet.host || __('(unnamed host)', 'dozuki'),
                        path: path.path || __('(unnamed path)', 'dozuki'),
                        glob: path.glob || false,
                        type: redaction.type,
                        value: redaction.value,
                        remove: redaction.remove || false,
                        hostIndex,
                        pathIndex,
                        redactionIndex,
                    });
                });
            });
        });
        return items;
    }, [redactionSets]);

    const fields = [
        {
            id: 'host',
            label: __('Host', 'dozuki'),
            enableHiding: false,
            enableSorting: true,
        },
        {
            id: 'path',
            label: __('Path', 'dozuki'),
            enableHiding: false,
            enableSorting: true,
            render: ({ item }: { item: FlatRedactionItem }) => (
                <span>
                    {item.path}
                    {item.glob && (
                        <span style={{ color: '#666', fontSize: '0.9em' }}>
                            {' '}
                            (glob)
                        </span>
                    )}
                </span>
            ),
        },
        {
            id: 'type',
            label: __('Redaction Type', 'dozuki'),
            enableHiding: false,
            enableSorting: true,
            render: ({ item }: { item: FlatRedactionItem }) => {
                const type = REDACTION_TYPES.find((t) => t.value === item.type);
                return type ? type.label : item.type;
            },
        },
        {
            id: 'value',
            label: __('Value', 'dozuki'),
            enableHiding: false,
            enableSorting: true,
            render: ({ item }: { item: FlatRedactionItem }) => (
                <code
                    style={{
                        fontSize: '0.9em',
                        backgroundColor: '#f6f7f7',
                        padding: '2px 4px',
                        borderRadius: '3px',
                    }}
                >
                    {item.value || __('(empty)', 'dozuki')}
                </code>
            ),
        },
        {
            id: 'remove',
            label: __('Remove', 'dozuki'),
            enableHiding: false,
            enableSorting: true,
            render: ({ item }: { item: FlatRedactionItem }) =>
                item.remove ? __('Yes', 'dozuki') : __('No', 'dozuki'),
        },
    ];

    const { data: filteredRedactions, paginationInfo } = filterSortAndPaginate(
        flatRedactions,
        view,
        fields
    );

    const actions = [
        {
            id: 'edit-redaction',
            label: __('Edit Redaction', 'dozuki'),
            callback: (items: FlatRedactionItem[]) => {
                if (items.length === 1) {
                    const item = items[0];
                    setEditingRedaction({
                        hostIndex: item.hostIndex,
                        pathIndex: item.pathIndex,
                        redactionIndex: item.redactionIndex,
                        isNew: false,
                    });
                }
            },
        },
        {
            id: 'add-redaction',
            label: __('Add Redaction to Path', 'dozuki'),
            callback: (items: FlatRedactionItem[]) => {
                if (items.length === 1) {
                    const item = items[0];
                    setEditingRedaction({
                        hostIndex: item.hostIndex,
                        pathIndex: item.pathIndex,
                        isNew: true,
                    });
                }
            },
        },
        {
            id: 'edit-path',
            label: __('Edit Path', 'dozuki'),
            callback: (items: FlatRedactionItem[]) => {
                if (items.length === 1) {
                    const item = items[0];
                    setEditingPath({
                        hostIndex: item.hostIndex,
                        pathIndex: item.pathIndex,
                        isNew: false,
                    });
                }
            },
        },
        {
            id: 'add-path',
            label: __('Add Path to Host', 'dozuki'),
            callback: (items: FlatRedactionItem[]) => {
                if (items.length === 1) {
                    const item = items[0];
                    setEditingPath({
                        hostIndex: item.hostIndex,
                        pathIndex: -1,
                        isNew: true,
                    });
                }
            },
        },
        {
            id: 'edit-host',
            label: __('Edit Host', 'dozuki'),
            callback: (items: FlatRedactionItem[]) => {
                if (items.length === 1) {
                    const item = items[0];
                    const currentHost = redactionSets[item.hostIndex].host;
                    const newHost = prompt(__('Enter new host:', 'dozuki'), currentHost);
                    if (newHost !== null && newHost !== currentHost) {
                        updateHost(item.hostIndex, newHost);
                    }
                }
            },
        },
        {
            id: 'delete',
            label: __('Delete Redaction', 'dozuki'),
            isDestructive: true,
            callback: (items: FlatRedactionItem[]) => {
                items.forEach((item) => {
                    const currentRedactions =
                        redactionSets[item.hostIndex].paths[item.pathIndex]
                            .redactions;
                    const newRedactions = currentRedactions.filter(
                        (_, i) => i !== item.redactionIndex
                    );
                    updateRedactions(
                        item.hostIndex,
                        item.pathIndex,
                        newRedactions
                    );
                });
            },
        },
    ];

    const handleSaveRedaction = (formData: {
        type: string;
        value: string;
        remove: boolean;
    }) => {
        if (!editingRedaction) return;

        const { hostIndex, pathIndex, redactionIndex, isNew } =
            editingRedaction;
        const currentRedactions =
            redactionSets[hostIndex].paths[pathIndex].redactions;

        let newRedactions;
        if (isNew) {
            newRedactions = [...currentRedactions, formData];
        } else {
            newRedactions = currentRedactions.map((redaction, i) =>
                i === redactionIndex ? { ...redaction, ...formData } : redaction
            );
        }

        updateRedactions(hostIndex, pathIndex, newRedactions);
        setEditingRedaction(null);
    };

    const handleSavePath = (formData: { path: string; glob: boolean }) => {
        if (!editingPath) return;

        const { hostIndex, pathIndex, isNew } = editingPath;
        if (isNew) {
            addPath(hostIndex);
            // Update the newly added path with the path data
            setTimeout(() => {
                const newPathIndex = redactionSets[hostIndex].paths.length - 1;
                updatePath(hostIndex, newPathIndex, 'path', formData.path);
                updatePath(hostIndex, newPathIndex, 'glob', formData.glob);
            }, 0);
        } else {
            updatePath(hostIndex, pathIndex, 'path', formData.path);
            updatePath(hostIndex, pathIndex, 'glob', formData.glob);
        }
        setEditingPath(null);
    };

    const getCurrentRedaction = () => {
        if (!editingRedaction || editingRedaction.isNew) {
            return { type: '*', value: '', remove: false };
        }
        const { hostIndex, pathIndex, redactionIndex } = editingRedaction;
        return redactionSets[hostIndex].paths[pathIndex].redactions[
            redactionIndex!
        ];
    };

    const getCurrentPath = () => {
        if (!editingPath || editingPath.isNew) {
            return { path: '', glob: false };
        }
        const { hostIndex, pathIndex } = editingPath;
        const pathData = redactionSets[hostIndex].paths[pathIndex];
        return { path: pathData.path, glob: pathData.glob || false };
    };

    return (
        <>
            <VStack spacing={4}>
                {flatRedactions.length === 0 ? (
                    <div
                        style={{
                            textAlign: 'center',
                            padding: '40px',
                            color: '#666',
                        }}
                    >
                        <p>
                            {__('No redaction rules configured yet.', 'dozuki')}
                        </p>
                        <p>{__('Click "Add Host" above to create your first redaction configuration.', 'dozuki')}</p>
                    </div>
                ) : (
                    <DataViews
                        data={filteredRedactions}
                        fields={fields}
                        view={view}
                        onChangeView={setView}
                        actions={actions}
                        isLoading={false}
                        paginationInfo={paginationInfo}
                        defaultLayouts={{ table: {} }}
                    />
                )}
            </VStack>

            {/* Redaction Edit Modal */}
            {editingRedaction && (
                <RedactionEditModal
                    redaction={getCurrentRedaction()}
                    onSave={handleSaveRedaction}
                    onCancel={() => setEditingRedaction(null)}
                    isNew={editingRedaction.isNew}
                />
            )}

            {/* Path Edit Modal */}
            {editingPath && (
                <PathEditModal
                    path={getCurrentPath()}
                    onSave={handleSavePath}
                    onCancel={() => setEditingPath(null)}
                    isNew={editingPath.isNew}
                />
            )}
        </>
    );
}

interface RedactionEditModalProps {
    redaction: RedactionItem;
    onSave: (data: { type: string; value: string; remove: boolean }) => void;
    onCancel: () => void;
    isNew: boolean;
}

function RedactionEditModal({
    redaction,
    onSave,
    onCancel,
    isNew,
}: RedactionEditModalProps) {
    const [type, setType] = useState(redaction.type);
    const [value, setValue] = useState(redaction.value);
    const [remove, setRemove] = useState(redaction.remove || false);

    const handleSave = () => {
        onSave({ type, value, remove });
    };

    return (
        <Modal
            title={
                isNew
                    ? __('Add Redaction', 'dozuki')
                    : __('Edit Redaction', 'dozuki')
            }
            onRequestClose={onCancel}
            size="medium"
        >
            <VStack spacing={4}>
                <SelectControl
                    label={__('Redaction Type', 'dozuki')}
                    value={type}
                    options={REDACTION_TYPES}
                    onChange={setType}
                    help={__('Choose what type of data to redact', 'dozuki')}
                />

                <TextControl
                    label={__('Value to Match', 'dozuki')}
                    value={value}
                    onChange={setValue}
                    placeholder="password, token, api_key, etc."
                    help={__(
                        'The parameter/header name or content to redact',
                        'dozuki'
                    )}
                />

                <CheckboxControl
                    label={__('Remove Property Entirely', 'dozuki')}
                    checked={remove}
                    onChange={setRemove}
                    help={__(
                        'If checked, the property will be removed completely instead of being redacted',
                        'dozuki'
                    )}
                />

                <HStack justify="right">
                    <Button variant="tertiary" onClick={onCancel}>
                        {__('Cancel', 'dozuki')}
                    </Button>
                    <Button variant="primary" onClick={handleSave}>
                        {isNew
                            ? __('Add Redaction', 'dozuki')
                            : __('Save Changes', 'dozuki')}
                    </Button>
                </HStack>
            </VStack>
        </Modal>
    );
}

interface PathEditModalProps {
    path: { path: string; glob: boolean };
    onSave: (data: { path: string; glob: boolean }) => void;
    onCancel: () => void;
    isNew: boolean;
}

function PathEditModal({ path, onSave, onCancel, isNew }: PathEditModalProps) {
    const [pathValue, setPathValue] = useState(path.path);
    const [globValue, setGlobValue] = useState(path.glob);

    const handleSave = () => {
        onSave({ path: pathValue, glob: globValue });
    };

    return (
        <Modal
            title={isNew ? __('Add Path', 'dozuki') : __('Edit Path', 'dozuki')}
            onRequestClose={onCancel}
            size="medium"
        >
            <VStack spacing={4}>
                <TextControl
                    label={__('Path', 'dozuki')}
                    value={pathValue}
                    onChange={setPathValue}
                    placeholder="/api/v1/users, /admin/*, /wp-json/**"
                    help={__('The URL path or pattern to match', 'dozuki')}
                />

                <CheckboxControl
                    label={__('Use Glob Pattern Matching', 'dozuki')}
                    checked={globValue}
                    onChange={setGlobValue}
                    help={__(
                        'If checked, wildcards like * and ** will be interpreted as patterns',
                        'dozuki'
                    )}
                />

                <HStack justify="right">
                    <Button variant="tertiary" onClick={onCancel}>
                        {__('Cancel', 'dozuki')}
                    </Button>
                    <Button variant="primary" onClick={handleSave}>
                        {isNew
                            ? __('Add Path', 'dozuki')
                            : __('Save Changes', 'dozuki')}
                    </Button>
                </HStack>
            </VStack>
        </Modal>
    );
}
