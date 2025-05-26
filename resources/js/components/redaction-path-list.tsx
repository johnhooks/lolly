import React from 'react';

import {
    Button,
    TextControl,
    CheckboxControl,
    CardDivider,
    __experimentalHStack as HStack,
    __experimentalVStack as VStack,
} from '@wordpress/components';
import { useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

import { default as settingStore } from '../store';
import type { PathRedaction } from '../store/types';

import RedactionForm from './redaction-form';

interface RedactionPathListProps {
    paths: PathRedaction[];
    hostIndex: number;
}

export default function RedactionPathList({
    paths,
    hostIndex,
}: RedactionPathListProps): React.ReactNode {
    const { updatePath, removePath, addPath, updateRedactions } =
        useDispatch(settingStore);

    return (
        <div>
            <h3 style={{ margin: 0, fontSize: '1rem' }}>
                {__('Paths', 'lolly')}
            </h3>
            {paths.map((pathItem, pathIndex) => (
                <div key={pathIndex}>
                    {pathIndex > 0 && <CardDivider />}
                    <VStack spacing={3} style={{ padding: '16px 0' }}>
                        <HStack>
                            <TextControl
                                label={__('Path', 'lolly')}
                                value={pathItem.path}
                                onChange={(value) =>
                                    updatePath(
                                        hostIndex,
                                        pathIndex,
                                        'path',
                                        value
                                    )
                                }
                                placeholder="/wp/v2/post"
                            />
                            <CheckboxControl
                                label={__('Glob', 'lolly')}
                                checked={pathItem.glob || false}
                                onChange={(value) =>
                                    updatePath(
                                        hostIndex,
                                        pathIndex,
                                        'glob',
                                        value
                                    )
                                }
                            />
                            <Button
                                isDestructive
                                variant="tertiary"
                                onClick={() => removePath(hostIndex, pathIndex)}
                            >
                                {__('Remove Path', 'lolly')}
                            </Button>
                        </HStack>

                        <RedactionForm
                            redactions={pathItem.redactions}
                            onUpdate={(redactions) =>
                                updateRedactions(
                                    hostIndex,
                                    pathIndex,
                                    redactions
                                )
                            }
                        />
                    </VStack>
                </div>
            ))}
            <CardDivider />
            <div style={{ padding: '16px 0' }}>
                <Button variant="secondary" onClick={() => addPath(hostIndex)}>
                    {__('Add Path', 'lolly')}
                </Button>
            </div>
        </div>
    );
}
