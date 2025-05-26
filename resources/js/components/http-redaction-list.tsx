import React from 'react';

import {
    Button,
    TextControl,
    Panel,
    PanelBody,
    __experimentalHStack as HStack,
    __experimentalVStack as VStack,
} from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

import { default as settingStore } from '../store';

import RedactionPathList from './redaction-path-list';

export default function HttpRedactionList(): React.ReactNode {
    const redactionSets = useSelect((select) => {
        return select(settingStore).getHttpRedactions();
    }, []);

    const { updateHost, removeHost, addHost } = useDispatch(settingStore);
    return (
        <>
            <h2>{__('HTTP Redaction Configuration', 'lolly')}</h2>
            <VStack spacing={4}>
                <Panel>
                    {redactionSets.map((redactionSet, setIndex) => (
                        <PanelBody
                            key={setIndex}
                            title={
                                redactionSet.host ||
                                __('New Host Configuration', 'lolly')
                            }
                            initialOpen={false}
                        >
                            <VStack spacing={3}>
                                <HStack>
                                    <TextControl
                                        label={__('Host', 'lolly')}
                                        value={redactionSet.host}
                                        onChange={(value) =>
                                            updateHost(setIndex, value)
                                        }
                                        placeholder="example.com"
                                    />
                                    <Button
                                        isDestructive
                                        variant="tertiary"
                                        onClick={() => removeHost(setIndex)}
                                    >
                                        {__('Remove Host', 'lolly')}
                                    </Button>
                                </HStack>

                                <RedactionPathList
                                    paths={redactionSet.paths}
                                    hostIndex={setIndex}
                                />
                            </VStack>
                        </PanelBody>
                    ))}
                </Panel>
                <Button variant="secondary" onClick={addHost}>
                    {__('Add Host Configuration', 'lolly')}
                </Button>
            </VStack>
        </>
    );
}
