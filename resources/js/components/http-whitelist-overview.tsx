import React, { useState, useMemo } from 'react';

import {
    Button,
    Modal,
    TextControl,
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
import type { PathWhitelist } from '../store/types';

interface FlatWhitelistItem {
    id: string;
    host: string;
    hostGlob: boolean;
    path: string;
    pathGlob: boolean;
    hostIndex: number;
    pathIndex: number;
}

interface EditingPath {
    hostIndex: number;
    pathIndex: number;
    isNew: boolean;
}

export default function HttpWhitelistOverview(): React.ReactNode {
    const whitelistSets = useSelect((select) => {
        return select(settingStore).getHttpWhitelist();
    }, []);

    const {
        updateWhitelistHost,
        updateWhitelistPath,
        addWhitelistPath,
        removeWhitelistPath,
    } = useDispatch(settingStore);

    const [editingPath, setEditingPath] = useState<EditingPath | null>(null);
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
        fields: ['path', 'hostGlob', 'pathGlob'],
        layout: {},
    });

    // Flatten all whitelist entries for DataViews
    const flatWhitelistItems = useMemo(() => {
        const items: FlatWhitelistItem[] = [];
        whitelistSets.forEach((whitelistSet, hostIndex) => {
            whitelistSet.paths.forEach((path, pathIndex) => {
                items.push({
                    id: `${hostIndex}-${pathIndex}`,
                    host: whitelistSet.host || __('(unnamed host)', 'dozuki'),
                    hostGlob: whitelistSet.glob || false,
                    path: path.path || __('(unnamed path)', 'dozuki'),
                    pathGlob: path.glob || false,
                    hostIndex,
                    pathIndex,
                });
            });
        });
        return items;
    }, [whitelistSets]);

    const fields = [
        {
            id: 'host',
            label: __('Host', 'dozuki'),
            enableHiding: false,
            enableSorting: true,
            render: ({ item }: { item: FlatWhitelistItem }) => (
                <span>
                    {item.host}
                    {item.hostGlob && (
                        <span style={{ color: '#666', fontSize: '0.9em' }}>
                            {' '}
                            (glob)
                        </span>
                    )}
                </span>
            ),
        },
        {
            id: 'path',
            label: __('Path', 'dozuki'),
            enableHiding: false,
            enableSorting: true,
            render: ({ item }: { item: FlatWhitelistItem }) => (
                <span>
                    {item.path}
                    {item.pathGlob && (
                        <span style={{ color: '#666', fontSize: '0.9em' }}>
                            {' '}
                            (glob)
                        </span>
                    )}
                </span>
            ),
        },
    ];

    const { data: filteredWhitelistItems, paginationInfo } =
        filterSortAndPaginate(flatWhitelistItems, view, fields);

    const actions = [
        {
            id: 'edit-path',
            label: __('Edit Path', 'dozuki'),
            callback: (items: FlatWhitelistItem[]) => {
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
            callback: (items: FlatWhitelistItem[]) => {
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
            callback: (items: FlatWhitelistItem[]) => {
                if (items.length === 1) {
                    const item = items[0];
                    const currentHost = whitelistSets[item.hostIndex].host;
                    const newHost = prompt(
                        __('Enter new host:', 'dozuki'),
                        currentHost
                    );
                    if (newHost !== null && newHost !== currentHost) {
                        updateWhitelistHost(item.hostIndex, newHost);
                    }
                }
            },
        },
        {
            id: 'delete',
            label: __('Delete Path', 'dozuki'),
            isDestructive: true,
            callback: (items: FlatWhitelistItem[]) => {
                items.forEach((item) => {
                    removeWhitelistPath(item.hostIndex, item.pathIndex);
                });
            },
        },
    ];

    const handleSavePath = (formData: { path: string; glob: boolean }) => {
        if (!editingPath) return;

        const { hostIndex, pathIndex, isNew } = editingPath;
        if (isNew) {
            addWhitelistPath(hostIndex);
            // Update the newly added path with the path data
            setTimeout(() => {
                const newPathIndex = whitelistSets[hostIndex].paths.length - 1;
                updateWhitelistPath(
                    hostIndex,
                    newPathIndex,
                    'path',
                    formData.path
                );
                updateWhitelistPath(
                    hostIndex,
                    newPathIndex,
                    'glob',
                    formData.glob
                );
            }, 0);
        } else {
            updateWhitelistPath(hostIndex, pathIndex, 'path', formData.path);
            updateWhitelistPath(hostIndex, pathIndex, 'glob', formData.glob);
        }
        setEditingPath(null);
    };

    const getCurrentPath = () => {
        if (!editingPath || editingPath.isNew) {
            return { path: '', glob: false };
        }
        const { hostIndex, pathIndex } = editingPath;
        const pathData = whitelistSets[hostIndex].paths[pathIndex];
        return { path: pathData.path, glob: pathData.glob || false };
    };

    return (
        <>
            <VStack spacing={4}>
                {flatWhitelistItems.length === 0 ? (
                    <div
                        style={{
                            textAlign: 'center',
                            padding: '40px',
                            color: '#666',
                        }}
                    >
                        <p>
                            {__('No whitelist rules configured yet.', 'dozuki')}
                        </p>
                        <p>
                            {__(
                                'Click "Add Host" above to create your first whitelist configuration.',
                                'dozuki'
                            )}
                        </p>
                    </div>
                ) : (
                    <DataViews
                        data={filteredWhitelistItems}
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
                    help={__(
                        'The URL path or pattern to match for whitelisting',
                        'dozuki'
                    )}
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
